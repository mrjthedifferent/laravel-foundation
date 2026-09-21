<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Modules\User\Services\ImpersonationService;

/**
 * Signs a Super Admin in as another user, remembering the real actor in the
 * session so audits stay attributed to them and they can return later
 * (StopImpersonationAction).
 */
final readonly class StartImpersonationAction
{
    public function __construct(private ImpersonationService $impersonation) {}

    public function execute(User $impersonator, User $target): void
    {
        $this->impersonation->recordAudit($target, ImpersonationService::STARTED_EVENT);

        session()->put(ImpersonationService::SESSION_KEY, $impersonator->id);

        Auth::guard('web')->login($target);
    }
}
