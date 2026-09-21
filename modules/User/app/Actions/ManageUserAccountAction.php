<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\User\Enum\AccountAction;

final readonly class ManageUserAccountAction
{
    /**
     * Manage a user account — reset profile data or permanently delete.
     */
    public function execute(User $user, AccountAction $action): bool
    {
        try {
            return DB::transaction(function () use ($user, $action) {
                // Delete Firebase tokens
                if ($user->firebaseTokens()->exists()) {
                    $user->firebaseTokens()->delete();
                }

                // Delete devices
                if ($user->devices()->exists()) {
                    $user->devices()->delete();
                }

                if ($action === AccountAction::Reset) {
                    $user->update([
                        'name' => null,
                        'image' => null,
                        'gender' => null,
                    ]);

                    return true;
                }

                if ($action === AccountAction::Delete) {
                    $user->delete();

                    return true;
                }

                return false;
            });
        } catch (\Exception $e) {
            Log::error("Error {$action->value} user account: ".$e->getMessage());

            return false;
        }
    }
}
