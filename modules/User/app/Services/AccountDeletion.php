<?php

declare(strict_types=1);

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Notification\Actions\NotifyAction;
use Modules\Notification\Enum\NotificationType;
use Modules\User\Enum\DeletionStatus;
use Modules\User\Events\AccountDeleting;
use Modules\User\Events\AccountDeletionCancelled;
use Modules\User\Events\AccountDeletionRequested;
use Modules\User\Exceptions\AccountDeletionBlocked;
use Modules\User\Models\AccountDeletionRequest;
use Modules\User\Models\UserDocument;
use Modules\User\Models\UserLoginHistory;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Services\FileManagerService;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Throwable;

/**
 * Account deletion that keeps other people's records safe.
 *
 * A request signs the person out everywhere. With foundation.account_deletion.automatic off it
 * waits for staff (Administration → Deletion requests); otherwise it is scheduled at once. After
 * grace_days the account is anonymized: the user row stays (payments, orders, reviews and logs
 * keep pointing at it) but every personal detail goes, and apps remove their own data on
 * AccountDeleting. Signing in before then cancels the request.
 */
final class AccountDeletion
{
    public const string REVIEW_PERMISSION = 'Review Account Deletion';

    public const string ANONYMIZE_PERMISSION = 'Anonymize Account';

    /** The person's request that is still waiting to happen, if any. */
    public function open(User $user): ?AccountDeletionRequest
    {
        return AccountDeletionRequest::query()->where('user_id', $user->id)->open()->latest('id')->first();
    }

    /**
     * What must be settled first (empty: nothing blocks).
     *
     * @return list<string>
     */
    public function blockers(User $user): array
    {
        $blockers = $user->is_super_admin ? [__('user::user.deletion.blocked_super_admin')] : [];

        return [...$blockers, ...Foundation::resolveAccountDeletionBlockers($user)];
    }

    public function needsReview(): bool
    {
        return ! (bool) config('foundation.account_deletion.automatic', true);
    }

    /**
     * @throws AccountDeletionBlocked
     */
    public function request(User $user, string $source = 'app'): AccountDeletionRequest
    {
        if (($existing = $this->open($user)) !== null) {
            return $existing;
        }

        $blockers = $this->blockers($user);
        if ($blockers !== []) {
            throw new AccountDeletionBlocked($blockers);
        }

        $review = $this->needsReview();
        $request = AccountDeletionRequest::create([
            'user_id' => $user->id,
            'status' => $review ? DeletionStatus::PendingReview : DeletionStatus::Scheduled,
            'source' => $source,
            'requested_at' => now(),
            'scheduled_for' => $review ? null : $this->graceEnds(now()),
        ]);

        $this->signOutEverywhere($user);
        AccountDeletionRequested::dispatch($user, $request);

        $this->notify($user, 'deletion.notify_requested_title', $review ? 'deletion.notify_requested_review' : 'deletion.notify_requested_scheduled', [
            'date' => $request->scheduled_for?->toDateString(),
        ]);
        if ($review) {
            $this->notifyReviewers($user);
        }

        return $request;
    }

    public function approve(AccountDeletionRequest $request, User $staff): void
    {
        abort_unless($request->status === DeletionStatus::PendingReview, 409);

        $request->update([
            'status' => DeletionStatus::Scheduled,
            'scheduled_for' => $this->graceEnds($request->requested_at)->max(now()),
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
        ]);

        $this->notify($request->user, 'deletion.notify_requested_title', 'deletion.notify_approved', [
            'date' => $request->scheduled_for?->toDateString(),
        ]);
    }

    public function reject(AccountDeletionRequest $request, User $staff, string $reason): void
    {
        abort_unless($request->status->isOpen(), 409);

        $request->update([
            'status' => DeletionStatus::Rejected,
            'reviewed_by' => $staff->id,
            'reviewed_at' => now(),
            'reason' => $reason,
        ]);

        AccountDeletionCancelled::dispatch($request->user, $request);
        $this->notify($request->user, 'deletion.notify_rejected_title', 'deletion.notify_rejected', ['reason' => $reason]);
    }

    /** The person's own undo (from the app, or by signing in again). */
    public function cancel(User $user, bool $bySignIn = false): bool
    {
        $request = $this->open($user);
        if ($request === null) {
            return false;
        }

        $request->update(['status' => DeletionStatus::Cancelled]);
        AccountDeletionCancelled::dispatch($user, $request);
        $this->notify($user, 'deletion.notify_cancelled_title', $bySignIn ? 'deletion.notify_cancelled_sign_in' : 'deletion.notify_cancelled');

        return true;
    }

    /**
     * Removes the person: apps clean up their data on AccountDeleting, then the user row keeps
     * only what other records need (its id) and loses everything that identifies them.
     */
    public function anonymize(User $user, ?AccountDeletionRequest $request = null): void
    {
        $request ??= $this->open($user);

        DB::transaction(function () use ($user, $request): void {
            AccountDeleting::dispatch($user, $request);

            // Each document deletes its own files (HasImageAttribute).
            UserDocument::query()->where('user_id', $user->id)->get()->each->delete();
            FileManagerService::deleteFile($user->getRawOriginal('image'));

            $user->tokens()->delete();
            $this->forgetDevices($user);
            $user->syncRoles([]);
            $user->syncPermissions([]);
            $this->signOutEverywhere($user);

            // The audit trail of the person's own profile holds their old phone and email.
            Audit::query()->where('auditable_type', $user->getMorphClass())->where('auditable_id', $user->id)->delete();

            // A plain query: no audit row (it would record the old values), no casts or events.
            User::query()->whereKey($user->id)->update([
                'name' => __('user::user.deletion.deleted_user'),
                'email' => null,
                'phone' => null,
                'image' => null,
                'gender' => null,
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'is_active' => false,
                'anonymized_at' => now(),
            ]);
            $user->refresh();

            $request?->update(['status' => DeletionStatus::Done, 'completed_at' => now()]);
        });
    }

    /**
     * Scheduled requests whose date has come; anything blocked meanwhile waits and staff are told.
     *
     * @return array{anonymized: int, waiting: int}
     */
    public function runDue(): array
    {
        $done = 0;
        $waiting = 0;

        $due = AccountDeletionRequest::query()->with('user')
            ->where('status', DeletionStatus::Scheduled)
            ->where('scheduled_for', '<=', now())
            ->get();

        foreach ($due as $request) {
            if ($this->blockers($request->user) !== []) {
                $waiting++;
                $this->notifyReviewers($request->user, blocked: true);

                continue;
            }

            try {
                $this->anonymize($request->user, $request);
                $done++;
            } catch (Throwable $e) {
                Log::error('Account anonymization failed for user '.$request->user_id.': '.$e->getMessage());
            }
        }

        return ['anonymized' => $done, 'waiting' => $waiting];
    }

    /** Login history and audits of anonymized users are kept security_log_days, then dropped. */
    public function pruneSecurityLogs(): int
    {
        $before = now()->subDays(max(0, (int) config('foundation.account_deletion.security_log_days', 365)));
        $anonymized = User::query()->whereNotNull('anonymized_at')->select('id');

        return UserLoginHistory::query()->whereIn('user_id', $anonymized)->where('logged_in_at', '<', $before)->delete()
            + Audit::query()->whereIn('user_id', $anonymized)->where('created_at', '<', $before)->delete();
    }

    private function graceEnds(Carbon $from): Carbon
    {
        return $from->copy()->addDays(max(0, (int) config('foundation.account_deletion.grace_days', 30)));
    }

    private function signOutEverywhere(User $user): void
    {
        $user->tokens()->delete();

        if (config('session.driver') === 'database') {
            DB::table((string) config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }

    private function forgetDevices(User $user): void
    {
        foreach (['firebaseTokens', 'devices'] as $relation) {
            try {
                $user->{$relation}()->delete();
            } catch (Throwable) {
                // Relation not registered in this project.
            }
        }
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    private function notify(User $user, string $title, string $body, array $replace = []): void
    {
        try {
            NotifyAction::toUser($user, __("user::user.{$title}"), __("user::user.{$body}", $replace), NotificationType::Info, ['type' => 'account_deletion'], ['database', 'fcm']);
        } catch (Throwable $e) {
            Log::warning('Account deletion notice not sent: '.$e->getMessage());
        }
    }

    private function notifyReviewers(User $subject, bool $blocked = false): void
    {
        $permission = Permission::query()->where('name', self::REVIEW_PERMISSION)->exists();
        $reviewers = User::query()
            ->where(fn ($q) => $q->where('is_super_admin', true)
                ->when($permission, fn ($q) => $q->orWhereHas('permissions', fn ($p) => $p->where('name', self::REVIEW_PERMISSION))
                    ->orWhereHas('roles.permissions', fn ($p) => $p->where('name', self::REVIEW_PERMISSION))))
            ->where('is_active', true)
            ->get();

        foreach ($reviewers as $reviewer) {
            $this->notify($reviewer, 'deletion.notify_review_title', $blocked ? 'deletion.notify_review_blocked' : 'deletion.notify_review', [
                'name' => $subject->name ?? $subject->phone ?? '#'.$subject->id,
            ]);
        }
    }
}
