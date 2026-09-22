<?php

namespace Modules\Otp\Queries;

use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\VerificationCode;
use Mrj\Foundation\Support\QueryBuilder;

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
final class VerificationCodeQuery extends QueryBuilder
{
    public static function make(): self
    {
        return new self(VerificationCode::query());
    }

    /**
     * Filter by contact type (email or phone)
     */
    public function filterByContactType(?ContactType $contactType): self
    {
        if ($contactType !== null) {
            $this->query->where('contact_type', $contactType->value);
        }

        return $this;
    }

    /**
     * Filter by verification status
     */
    public function filterByVerified(?bool $isVerified): self
    {
        if ($isVerified !== null) {
            $this->query->where('is_verified', $isVerified);
        }

        return $this;
    }

    /**
     * Filter by expiry status (expired/active)
     */
    public function filterByExpired(?bool $expired): self
    {
        if ($expired !== null) {
            $expired
                ? $this->query->where('expires_at', '<=', now())
                : $this->query->where('expires_at', '>', now());
        }

        return $this;
    }

    /**
     * Filter by date range
     */
    public function filterByDateRange(?string $from, ?string $to): self
    {
        if ($from) {
            $this->query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $this->query->whereDate('created_at', '<=', $to);
        }

        return $this;
    }

    /**
     * Search by contact (email or phone)
     */
    public function search(?string $search): self
    {
        if (filled($search)) {
            $this->whereLike(['contact'], $search);
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
     * Get count of results
     */
    public function count(): int
    {
        return $this->query->count();
    }
}
