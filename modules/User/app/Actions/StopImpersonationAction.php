<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use LogicException;
use Modules\User\Services\ImpersonationService;

/**
 * Ends an impersonation: revokes any API tokens minted while impersonating,
 * records the end on the audit trail and signs the Super Admin back in.
 */
final readonly class StopImpersonationAction
{
    public function __construct(private ImpersonationService $impersonation) {}

    /**
     * @return User The user who was being impersonated.
     */
    public function execute(): User
    {
        $impersonatorId = $this->impersonation->impersonatorId();
        $target = Auth::guard('web')->user();

        if ($impersonatorId === null || ! $target instanceof User) {
            throw new LogicException('No impersonation is in progress.');
        }

        $target->tokens()
            ->where('abilities', 'like', '%"'.ImpersonationService::tokenAbility($impersonatorId).'"%')
            ->delete();

        $this->impersonation->recordAudit($target, ImpersonationService::ENDED_EVENT);

        session()->forget(ImpersonationService::SESSION_KEY);

        if (! Auth::guard('web')->loginUsingId($impersonatorId)) {
            Auth::guard('web')->logout();
        }

        return $target;
    }
}
