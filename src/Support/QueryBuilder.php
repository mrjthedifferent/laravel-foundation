<?php

namespace Mrj\Foundation\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base for a module's fluent query-builder wrapper. The underlying Eloquent
 * Builder is mutable — calling ->where() on it mutates and returns the same
 * instance — so a fluent filter method here mutates $this->query and
 * `return $this` rather than wrapping a "new" instance that would hold that
 * very same mutated Builder anyway.
 *
 * A subclass names its model with `@extends QueryBuilder<Model>` so that get()
 * returns a collection of that model.
 *
 * @template TModel of Model
 *
 * @api
 */
abstract class QueryBuilder
{
    /**
     * @param  Builder<TModel>  $query
     */
    public function __construct(protected Builder $query) {}

    /**
     * A LIKE search across one or more columns, with the term escaped so a
     * literal % or _ is matched literally rather than treated as a wildcard
     * (see escapeLike() in src/Helpers/common.php). The explicit ESCAPE
     * clause is required for this to work on SQLite, which — unlike MySQL
     * and PostgreSQL — has no default LIKE escape character.
     *
     * On PostgreSQL each column is cast to text (LIKE is not defined for
     * types such as inet, uuid or numbers) and matched with ILIKE, so search
     * is case-insensitive there as it is on MySQL and SQLite.
     *
     * @param  list<string>  $columns
     */
    protected function whereLike(array $columns, string $term): static
    {
        self::applyLike($this->query, $columns, $term);

        return $this;
    }

    /**
     * The same escaped, driver-aware LIKE search for code that holds a plain
     * Eloquent builder rather than a QueryBuilder subclass.
     *
     * @param  Builder<*>  $query
     * @param  list<string>  $columns
     */
    public static function applyLike(Builder $query, array $columns, string $term): void
    {
        $like = '%'.escapeLike($term).'%';
        $pgsql = $query->getModel()->getConnection()->getDriverName() === 'pgsql';

        $query->where(function (Builder $q) use ($columns, $like, $pgsql): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                $sql = $pgsql ? "CAST({$column} AS TEXT) ILIKE ? ESCAPE ?" : "{$column} LIKE ? ESCAPE ?";
                $q->{$method}($sql, [$like, '\\']);
            }
        });
    }

    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        return $this->query
            ->paginate(cappedPerPage($perPage ?? (int) config('foundation.pagination.default', 10)))
            ->withQueryString();
    }

    /**
     * @return Collection<int, TModel>
     */
    public function get(): Collection
    {
        return $this->query->get();
    }
}
