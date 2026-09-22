<?php

namespace Modules\Notification\Queries;

use Modules\Notification\Models\Notification;
use Mrj\Foundation\Support\QueryBuilder;

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
 *     ->paginate();
 */
final class NotificationQuery extends QueryBuilder
{
    public static function make(): self
    {
        return new self(Notification::query());
    }

    /**
     * Scope notifications to a specific notifiable (e.g. user).
     */
    public function forUser(object $notifiable): self
    {
        $this->query
            ->where('notifiable_type', get_class($notifiable))
            ->where('notifiable_id', $notifiable->id);

        return $this;
    }

    /**
     * Filter by read/unread status.
     */
    public function filterByReadStatus(?bool $isRead): self
    {
        if ($isRead !== null) {
            $isRead
                ? $this->query->whereNotNull('read_at')
                : $this->query->whereNull('read_at');
        }

        return $this;
    }

    /**
     * Filter by notification type string.
     */
    public function filterByType(?string $type): self
    {
        if (filled($type)) {
            $this->query->where('type', $type);
        }

        return $this;
    }

    /**
     * Order by most recent first.
     */
    public function orderByLatest(): self
    {
        $this->query->latest('created_at');

        return $this;
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
