<?php

namespace Modules\Notification\Policies;

use App\Models\User;

final class PushNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('View Push Notification');
    }

    public function create(User $user): bool
    {
        return $user->can('Create Push Notification');
    }
}
