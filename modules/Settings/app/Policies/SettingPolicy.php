<?php

namespace Modules\Settings\Policies;

use App\Models\User;

class SettingPolicy
{
    public function editSystem(User $user): bool
    {
        return $user->can('Edit System Setting');
    }

    public function developer(User $user): bool
    {
        return $user->can('Developer Setting');
    }

    public function editSpecial(User $user): bool
    {
        return $user->can('Edit Special Setting');
    }
}
