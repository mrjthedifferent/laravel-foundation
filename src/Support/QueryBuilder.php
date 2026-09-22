<?php

namespace Mrj\Foundation\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base for a module's fluent query-builder wrapper. The underlying Eloquent
 * Builder is mutable — calling ->where() on it mutates and returns the same
 * instance — so a fluent filter method here mutates $this->query and
 * `return $this` rather than wrapping a "new" instance that would hold that
 * very same mutated Builder anyway.
 *
 * @api
 */
abstract class QueryBuilder
{
    public function __construct(protected Builder $query) {}

    /**
     * A LIKE search across one or more columns, with the term escaped so a
     * literal % or _ is matched literally rather than treated as a wildcard
     * (see escapeLike() in src/Helpers/common.php). The explicit ESCAPE
     * clause is required for this to work on SQLite, which — unlike MySQL
     * and PostgreSQL — has no default LIKE escape character.
     *
     * @param  list<string>  $columns
     */
    protected function whereLike(array $columns, string $term): static
    {
        $like = '%'.escapeLike($term).'%';

        $this->query->where(function (Builder $q) use ($columns, $like): void {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                $q->{$method}("{$column} LIKE ? ESCAPE ?", [$like, '\\']);
            }
        });

        return $this;
    }

    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        return $this->query
            ->paginate(cappedPerPage($perPage ?? (int) config('foundation.pagination.default', 10)))
            ->withQueryString();
    }

    public function get(): Collection
    {
        return $this->query->get();
    }
}
