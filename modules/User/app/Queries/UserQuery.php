<?php

namespace Modules\User\Queries;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Mrj\Foundation\Enum\PaginationEnum;

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
 *     ->paginate(PaginationEnum::DEFAULT_LIST);
 */
final readonly class UserQuery
{
    public function __construct(
        private Builder $query
    ) {}

    public static function make(): self
    {
        return new self(User::query());
    }

    /**
     * Include relationships in the query
     */
    public function withRelations(array $relations = ['roles']): self
    {
        return new self($this->query->with($relations));
    }

    /**
     * Filter users by role ID
     */
    public function filterByRole(?int $roleId): self
    {
        if ($roleId === null) {
            return $this;
        }

        return new self(
            $this->query->whereHas('roles', fn ($q) => $q->where('id', $roleId))
        );
    }

    /**
     * Filter users by active status
     */
    public function filterByStatus(?bool $isActive): self
    {
        if ($isActive === null) {
            return $this;
        }

        return new self($this->query->where('is_active', $isActive));
    }

    /**
     * Filter users by gender
     */
    public function filterByGender(?string $gender): self
    {
        if (empty($gender)) {
            return $this;
        }

        return new self($this->query->where('gender', $gender));
    }

    /**
     * Search users by name, email or phone (case-insensitive).
     */
    public function search(?string $search): self
    {
        if (empty($search)) {
            return $this;
        }

        $like = $this->query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        // A phone is stored as +{country}{number}; a typed local form starts with 0.
        $digits = ltrim((string) preg_replace('/\D/', '', $search), '0');

        return new self(
            $this->query->where(function ($q) use ($search, $like, $digits) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('email', $like, "%{$search}%")
                    ->when(strlen($digits) >= 3, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%"));
            })
        );
    }

    /**
     * Filter by date range on a given column
     */
    public function filterByDateRange(string $column, ?string $from, ?string $to): self
    {
        $query = $this->query;

        if ($from) {
            $query = $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query = $query->whereDate($column, '<=', $to);
        }

        return new self($query);
    }

    /**
     * Filter by phone verification status
     */
    public function phoneVerified(?bool $verified = true): self
    {
        if ($verified === null) {
            return $this;
        }

        return new self(
            $verified
                ? $this->query->whereNotNull('phone_verified_at')
                : $this->query->whereNull('phone_verified_at')
        );
    }

    /**
     * Filter by email verification status
     */
    public function emailVerified(?bool $verified = true): self
    {
        if ($verified === null) {
            return $this;
        }

        return new self(
            $verified
                ? $this->query->whereNotNull('email_verified_at')
                : $this->query->whereNull('email_verified_at')
        );
    }

    /**
     * Order by latest created
     */
    public function orderByLatest(): self
    {
        return new self($this->query->orderBy('id', 'desc'));
    }

    /**
     * Order by name
     */
    public function orderByName(string $direction = 'asc'): self
    {
        return new self($this->query->orderBy('name', $direction));
    }

    /**
     * Limit the number of results
     */
    public function limit(int $limit): self
    {
        return new self($this->query->limit($limit));
    }

    /**
     * Get paginated results
     */
    public function paginate(int $perPage = PaginationEnum::DEFAULT_LIST): LengthAwarePaginator
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all results
     */
    public function get(): Collection
    {
        return $this->query->get();
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
