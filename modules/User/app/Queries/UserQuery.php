<?php

namespace Modules\User\Queries;

use App\Models\User;
use Illuminate\Support\LazyCollection;
use Mrj\Foundation\Support\QueryBuilder;

/**
 * User Query Builder
 *
 * Provides a fluent interface for querying users with common filters.
 * Follows the Builder pattern for chainable, testable queries.
 *
 * @example
 * UserQuery::make()
 *     ->withRelations(['roles'])
 *     ->filterByRole($roleId)
 *     ->search($searchTerm)
 *     ->paginate();
 */
final class UserQuery extends QueryBuilder
{
    public static function make(): self
    {
        return new self(User::query());
    }

    /**
     * Include relationships in the query
     */
    public function withRelations(array $relations = ['roles']): self
    {
        $this->query->with($relations);

        return $this;
    }

    /**
     * Filter users by role ID
     */
    public function filterByRole(?int $roleId): self
    {
        if ($roleId !== null) {
            $this->query->whereHas('roles', fn ($q) => $q->where('id', $roleId));
        }

        return $this;
    }

    /**
     * Filter users by active status
     */
    public function filterByStatus(?bool $isActive): self
    {
        if ($isActive !== null) {
            $this->query->where('is_active', $isActive);
        }

        return $this;
    }

    /**
     * Filter users by gender
     */
    public function filterByGender(?string $gender): self
    {
        if (filled($gender)) {
            $this->query->where('gender', $gender);
        }

        return $this;
    }

    /**
     * Search users by name, email or phone (case-insensitive).
     */
    public function search(?string $search): self
    {
        if (empty($search)) {
            return $this;
        }

        $operator = $this->query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $like = '%'.escapeLike($search).'%';

        // A phone is stored as +{country}{number}; a typed local form starts with 0.
        $digits = ltrim((string) preg_replace('/\D/', '', $search), '0');
        $digitsLike = '%'.escapeLike($digits).'%';

        $this->query->where(function ($q) use ($like, $operator, $digits, $digitsLike): void {
            $q->whereRaw("name {$operator} ? ESCAPE ?", [$like, '\\'])
                ->orWhereRaw("email {$operator} ? ESCAPE ?", [$like, '\\'])
                ->when(strlen($digits) >= 3, fn ($q) => $q->orWhereRaw('phone LIKE ? ESCAPE ?', [$digitsLike, '\\']));
        });

        return $this;
    }

    /**
     * Filter by date range on a given column
     */
    public function filterByDateRange(string $column, ?string $from, ?string $to): self
    {
        if ($from) {
            $this->query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $this->query->whereDate($column, '<=', $to);
        }

        return $this;
    }

    /**
     * Filter by phone verification status
     */
    public function phoneVerified(?bool $verified = true): self
    {
        if ($verified !== null) {
            $verified
                ? $this->query->whereNotNull('phone_verified_at')
                : $this->query->whereNull('phone_verified_at');
        }

        return $this;
    }

    /**
     * Filter by email verification status
     */
    public function emailVerified(?bool $verified = true): self
    {
        if ($verified !== null) {
            $verified
                ? $this->query->whereNotNull('email_verified_at')
                : $this->query->whereNull('email_verified_at');
        }

        return $this;
    }

    /**
     * Order by latest created
     */
    public function orderByLatest(): self
    {
        $this->query->orderBy('id', 'desc');

        return $this;
    }

    /**
     * Order by name
     */
    public function orderByName(string $direction = 'asc'): self
    {
        $this->query->orderBy('name', $direction);

        return $this;
    }

    /**
     * Limit the number of results
     */
    public function limit(int $limit): self
    {
        $this->query->limit($limit);

        return $this;
    }

    /**
     * Get first result
     */
    public function first(): ?User
    {
        return $this->query->first();
    }

    /**
     * Get count of results
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Check if any results exist
     */
    public function exists(): bool
    {
        return $this->query->exists();
    }

    /**
     * Lazy load for large datasets (memory efficient)
     */
    public function lazy(int $chunkSize = 1000): LazyCollection
    {
        return $this->query->lazy($chunkSize);
    }

    /**
     * Cursor for streaming results
     */
    public function cursor(): LazyCollection
    {
        return $this->query->cursor();
    }
}
