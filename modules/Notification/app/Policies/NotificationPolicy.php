<?php

namespace Modules\Notification\Policies;

use App\Models\User;
use Modules\Notification\Models\Notification;

/**
 * Notification Policy
 *
 * Centralized authorization logic for notification-related actions.
 * Users may only access, modify, and delete their own notifications.
 */
class NotificationPolicy
{
    /**
     * Any authenticated user can list their own notifications
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * User may only view a notification that belongs to them
     */
    public function view(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === get_class($user)
            && $notification->notifiable_id === $user->id;
    }

    /**
     * User may only delete their own notifications
     */
    public function delete(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === get_class($user)
            && $notification->notifiable_id === $user->id;
    }

    /**
     * User may only mark their own notifications as read
     */
    public function markAsRead(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === get_class($user)
            && $notification->notifiable_id === $user->id;
    }

    /**
     * User may only mark their own notifications as unread
     */
    public function markAsUnread(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === get_class($user)
            && $notification->notifiable_id === $user->id;
    }
}
