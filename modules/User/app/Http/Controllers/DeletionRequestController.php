<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\User\Enum\DeletionStatus;
use Modules\User\Models\AccountDeletionRequest;
use Modules\User\Services\AccountDeletion;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * Administration → Deletion requests: staff review requests (when automatic deletion is off),
 * see what is scheduled and the history, and can delete an account at once.
 */
final class DeletionRequestController extends Controller
{
    private const array TABS = ['pending_review', 'scheduled', 'history'];

    public function __construct(private readonly AccountDeletion $deletion) {}

    public function index(Request $request): View
    {
        $this->authorize(AccountDeletion::REVIEW_PERMISSION);

        $tab = in_array($request->query('tab'), self::TABS, true)
            ? (string) $request->query('tab')
            : ($this->deletion->needsReview() ? 'pending_review' : 'scheduled');

        $requests = AccountDeletionRequest::query()
            ->with(['user', 'reviewer'])
            ->when($tab === 'pending_review', fn ($q) => $q->where('status', DeletionStatus::PendingReview))
            ->when($tab === 'scheduled', fn ($q) => $q->where('status', DeletionStatus::Scheduled)->orderBy('scheduled_for'))
            ->when($tab === 'history', fn ($q) => $q->whereNotIn('status', DeletionStatus::open()))
            ->latest('id')
            ->paginate(perPage())
            ->withQueryString();

        // Re-checked live: something may have come up since the request.
        $blockers = $tab === 'history' ? [] : $requests->getCollection()
            ->mapWithKeys(fn (AccountDeletionRequest $r) => [$r->id => $this->deletion->blockers($r->user)])
            ->all();

        $counts = AccountDeletionRequest::query()->open()
            ->selectRaw('status, count(*) as total')->groupBy('status')
            ->pluck('total', 'status')->all();

        return view('user::deletion-requests.index', [
            'tab' => $tab,
            'requests' => $requests,
            'blockers' => $blockers,
            'counts' => $counts,
            'needsReview' => $this->deletion->needsReview(),
        ]);
    }

    public function approve(Request $request, AccountDeletionRequest $deletionRequest): RedirectResponse
    {
        $this->authorize(AccountDeletion::REVIEW_PERMISSION);

        /** @var User $staff */
        $staff = $request->user();
        $this->deletion->approve($deletionRequest, $staff);

        return back()->with('success', __('user::user.deletion.approved'));
    }

    public function reject(Request $request, AccountDeletionRequest $deletionRequest): RedirectResponse
    {
        $this->authorize(AccountDeletion::REVIEW_PERMISSION);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        /** @var User $staff */
        $staff = $request->user();
        $this->deletion->reject($deletionRequest, $staff, $data['reason']);

        return back()->with('success', __('user::user.deletion.rejected'));
    }

    public function anonymize(Request $request, AccountDeletionRequest $deletionRequest): RedirectResponse
    {
        $this->authorize(AccountDeletion::ANONYMIZE_PERMISSION);
        abort_unless($deletionRequest->status->isOpen(), 409);

        if ($this->deletion->blockers($deletionRequest->user) !== []) {
            return back()->with('error', __('user::user.deletion.still_blocked'));
        }

        /** @var User $staff */
        $staff = $request->user();
        $deletionRequest->update(['reviewed_by' => $deletionRequest->reviewed_by ?? $staff->id, 'reviewed_at' => $deletionRequest->reviewed_at ?? now()]);
        $this->deletion->anonymize($deletionRequest->user, $deletionRequest);

        return back()->with('success', __('user::user.deletion.anonymized'));
    }
}
