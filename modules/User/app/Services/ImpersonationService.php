<?php

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\User\Actions\StopImpersonationAction;
use Mrj\Foundation\Contracts\ImpersonationContext;
use OwenIt\Auditing\Events\AuditCustom;

/**
 * Answers "is a Super Admin impersonating someone right now, and who?" for
 * both transports:
 *  - web: the impersonator's id is kept in the session (StartImpersonationAction);
 *  - API: a Sanctum token issued during impersonation carries an
 *    `impersonator:{id}` ability (see tokenAbility()).
 *
 * Shared by the impersonation actions, UserPolicy, CheckUserIsActive, the audit
 * user resolver / tagging, the request log and the layout banner.
 */
final readonly class ImpersonationService implements ImpersonationContext
{
    public const string SESSION_KEY = 'impersonator_id';

    public const string STARTED_EVENT = 'impersonation_started';

    public const string ENDED_EVENT = 'impersonation_ended';

    private const string TOKEN_ABILITY_PREFIX = 'impersonator:';

    /**
     * The Sanctum token ability that marks a token as issued to an impersonator.
     */
    public static function tokenAbility(int $impersonatorId): string
    {
        return self::TOKEN_ABILITY_PREFIX.$impersonatorId;
    }

    /**
     * Id of the Super Admin behind the current request, or null when nobody
     * is being impersonated.
     */
    public function impersonatorId(): ?int
    {
        $request = request();

        if ($request->hasSession() && $request->session()->has(self::SESSION_KEY)) {
            return (int) $request->session()->get(self::SESSION_KEY);
        }

        $user = $request->user();
        $token = $user !== null && method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        if (! $token instanceof PersonalAccessToken) {
            return null;
        }

        foreach ((array) $token->abilities as $ability) {
            if (str_starts_with((string) $ability, self::TOKEN_ABILITY_PREFIX)) {
                return (int) substr((string) $ability, strlen(self::TOKEN_ABILITY_PREFIX));
            }
        }

        return null;
    }

    public function isImpersonating(): bool
    {
        return $this->impersonatorId() !== null;
    }

    public function impersonator(): ?User
    {
        $impersonatorId = $this->impersonatorId();

        return $impersonatorId === null ? null : User::find($impersonatorId);
    }

    /**
     * Id of the account being impersonated — the one the request is signed in as.
     */
    public function impersonatedUserId(): ?int
    {
        return $this->isImpersonating() ? request()->user()?->getAuthIdentifier() : null;
    }

    /**
     * Record an impersonation start/end on the impersonated user's audit trail.
     */
    public function stop(): void
    {
        app(StopImpersonationAction::class)->execute();
    }

    public function recordAudit(User $target, string $event): void
    {
        $target->auditEvent = $event;
        $target->isCustomEvent = true;
        $target->auditCustomOld = [];
        $target->auditCustomNew = ['impersonated_user' => $target->name];

        Event::dispatch(new AuditCustom($target));

        $target->isCustomEvent = false;
        $target->auditCustomOld = $target->auditCustomNew = [];
    }
}
