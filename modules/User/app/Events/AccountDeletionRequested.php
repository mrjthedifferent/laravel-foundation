<?php

declare(strict_types=1);

namespace Modules\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\User\Models\AccountDeletionRequest;

/**
 * A person asked to delete their account (signed out everywhere). Hide their public content now: listings, profiles.
 */
class AccountDeletionRequested
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public ?AccountDeletionRequest $request = null,
    ) {}
}
