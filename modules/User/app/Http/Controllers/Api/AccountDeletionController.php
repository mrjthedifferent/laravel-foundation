<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\User\Exceptions\AccountDeletionBlocked;
use Modules\User\Models\AccountDeletionRequest;
use Modules\User\Services\AccountDeletion;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

/**
 * In-app account deletion: what would block it, and the request itself (password confirmed).
 */
final class AccountDeletionController extends Controller
{
    public function __construct(private readonly AccountDeletion $deletion) {}

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return JsonResponseFactory::success(__('user::user.deletion.status_title'), [
            ...$this->describe($this->deletion->open($user)),
            'blockers' => $this->deletion->blockers($user),
            'needs_review' => $this->deletion->needsReview(),
            'grace_days' => (int) config('foundation.account_deletion.grace_days', 30),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($data['password'], $user->password)) {
            return JsonResponseFactory::unauthorized(__('user::user.errors.invalid_password'));
        }

        try {
            $deletion = $this->deletion->request($user, 'app');
        } catch (AccountDeletionBlocked $e) {
            return JsonResponseFactory::error(__('user::user.deletion.blocked'), ['blockers' => $e->blockers], 422);
        }

        return JsonResponseFactory::success(__('user::user.deletion.requested'), $this->describe($deletion), 201);
    }

    /**
     * @return array{status: string|null, scheduled_for: string|null, requested_at: string|null}
     */
    private function describe(?AccountDeletionRequest $request): array
    {
        return [
            'status' => $request?->status->value,
            'scheduled_for' => $request?->scheduled_for?->toIso8601String(),
            'requested_at' => $request?->requested_at->toIso8601String(),
        ];
    }
}
