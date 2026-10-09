<?php

declare(strict_types=1);

namespace Modules\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\User\Models\AccountDeletionRequest;

/**
 * The person signed in again or asked to keep the account, or staff rejected the request. Show what was hidden again.
 */
class AccountDeletionCancelled
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public ?AccountDeletionRequest $request = null,
    ) {}
}
