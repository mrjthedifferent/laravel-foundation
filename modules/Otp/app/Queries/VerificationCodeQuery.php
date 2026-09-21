<?php

namespace Modules\Otp\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\VerificationCode;

/**
 * VerificationCode Query Builder
 *
 * Provides a fluent interface for querying verification codes with common filters.
 * Follows the Builder pattern for chainable, testable queries.
 *
 * @example
 * VerificationCodeQuery::make()
 *     ->filterByContactType(ContactType::Email)
 *     ->filterByVerified(false)
 *     ->search('user@example.com')
 *     ->orderByLatest()
 *     ->paginate();
 */
final readonly class VerificationCodeQuery
{
    public function __construct(
        private Builder $query
    ) {}

    public static function make(): self
    {
        return new self(VerificationCode::query());
    }

    /**
     * Filter by contact type (email or phone)
     */
    public function filterByContactType(?ContactType $contactType): self
    {
        if ($contactType === null) {
            return $this;
        }

        return new self($this->query->where('contact_type', $contactType->value));
    }

    /**
     * Filter by verification status
     */
    public function filterByVerified(?bool $isVerified): self
    {
        if ($isVerified === null) {
            return $this;
        }

        return new self($this->query->where('is_verified', $isVerified));
    }

    /**
     * Filter by expiry status (expired/active)
     */
    public function filterByExpired(?bool $expired): self
    {
        if ($expired === null) {
            return $this;
        }

        return new self(
            $expired
                ? $this->query->where('expires_at', '<=', now())
                : $this->query->where('expires_at', '>', now())
        );
    }

    /**
     * Filter by date range
     */
    public function filterByDateRange(?string $from, ?string $to): self
    {
        $query = $this->query;

        if ($from) {
            $query = $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query = $query->whereDate('created_at', '<=', $to);
        }

        return new self($query);
    }

    /**
     * Search by contact (email or phone)
     */
    public function search(?string $search): self
    {
        if (empty($search)) {
            return $this;
        }

        return new self(
            $this->query->whereRaw('contact LIKE ? ESCAPE ?', ['%'.escapeLike($search).'%', '\\'])
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
     * Get paginated results
     */
    public function paginate(?int $perPage = null): LengthAwarePaginator
    {
        return $this->query->paginate($perPage ?? (int) config('foundation.pagination.default', 10));
    }

    /**
     * Get all results
     */
    public function get(): Collection
    {
        return $this->query->get();
    }

    /**
     * Get count of results
     */
    public function count(): int
    {
        return $this->query->count();
    }
}
