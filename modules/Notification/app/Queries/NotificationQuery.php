<?php

namespace Modules\Notification\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Notification\Models\Notification;

/**
 * Notification Query Builder
 *
 * Provides a fluent interface for querying notifications with common filters.
 *
 * @example
 * NotificationQuery::make()
 *     ->forUser($user)
 *     ->filterByReadStatus(false)
 *     ->orderByLatest()
 *     ->paginate(15);
 */
final readonly class NotificationQuery
{
    public function __construct(
        private Builder $query
    ) {}

    public static function make(): self
    {
        return new self(Notification::query());
    }

    /**
     * Scope notifications to a specific notifiable (e.g. user).
     */
    public function forUser(object $notifiable): self
    {
        return new self(
            $this->query
                ->where('notifiable_type', get_class($notifiable))
                ->where('notifiable_id', $notifiable->id)
        );
    }

    /**
     * Filter by read/unread status.
     */
    public function filterByReadStatus(?bool $isRead): self
    {
        if ($isRead === null) {
            return $this;
        }

        return new self(
            $isRead
                ? $this->query->whereNotNull('read_at')
                : $this->query->whereNull('read_at')
        );
    }

    /**
     * Filter by notification type string.
     */
    public function filterByType(?string $type): self
    {
        if (blank($type)) {
            return $this;
        }

        return new self($this->query->where('type', $type));
    }

    /**
     * Order by most recent first.
     */
    public function orderByLatest(): self
    {
        return new self($this->query->latest('created_at'));
    }

    /**
     * Get paginated results, preserving query string.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query->paginate($perPage)->withQueryString();
    }

    /**
     * Get all results as a collection.
     */
    public function get(): Collection
    {
        return $this->query->get();
    }

    /**
     * Get total count.
     */
    public function count(): int
    {
        return $this->query->count();
    }

    /**
     * Bulk-update matching rows (e.g. mark-all-as-read).
     *
     * @param  array<string, mixed>  $values
     * @return int Number of affected rows
     */
    public function update(array $values): int
    {
        return $this->query->update($values);
    }
}
