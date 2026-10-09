<?php

declare(strict_types=1);

namespace Modules\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\User\Models\AccountDeletionRequest;

/**
 * The account is about to be anonymized. Delete or anonymize the app's own data for this user here, inside the same transaction. Keep records other people or the law need (payments, orders with others) and remove the person from them.
 */
class AccountDeleting
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public ?AccountDeletionRequest $request = null,
    ) {}
}
