<?php

namespace Modules\Notification\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Notification\Actions\NotifyAction;
use Modules\Notification\Http\Requests\SendNotificationRequest;
use Modules\Notification\Http\Requests\UpdateFirebaseTokenRequest;
use Modules\Notification\Models\FirebaseToken;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

class ApiPushNotificationController extends Controller
{
    /**
     * Register or refresh a Firebase device token for the authenticated user.
     */
    public function updateFirebaseToken(UpdateFirebaseTokenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // The unique key is (token, device_id) and pushes route per user → token, so the upsert
        // must match on that pair (not user_id + device_id, which lets one device's token stay
        // claimed by a previous user and crashes with a duplicate-key violation when a new user
        // registers the same token on the same device). A device_id identifies one app install and
        // the FCM token is per-install, so we transfer the install to the current user and clear any
        // stale rows for the same device (rotated token) or the same token held elsewhere — otherwise
        // the previous holder would keep receiving this device's pushes.
        $token = DB::transaction(function () use ($user, $data) {
            FirebaseToken::query()
                ->where(fn ($q) => $q->where('device_id', $data['device_id'])->orWhere('token', $data['token']))
                ->where(fn ($q) => $q->where('device_id', '!=', $data['device_id'])->orWhere('token', '!=', $data['token']))
                ->delete();

            return FirebaseToken::updateOrCreate(
                ['token' => $data['token'], 'device_id' => $data['device_id']],
                ['user_id' => $user->id],
            );
        });

        return JsonResponseFactory::success(__('notification::notification.api.firebase_token_updated'), $token);
    }

    /**
     * Send a notification to the authenticated user via the specified channels.
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        NotifyAction::toUser(
            notifiable: $request->user(),
            title: $data['title'],
            body: $data['body'],
            data: $data['data'] ?? [],
            channels: $data['channels'],
        );

        return JsonResponseFactory::success(__('notification::notification.api.sent_via', ['channels' => implode(', ', $data['channels'])]));
    }
}
