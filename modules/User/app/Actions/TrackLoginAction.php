<?php

namespace Modules\User\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Modules\Notification\Actions\NotifyAction;
use Modules\Notification\Enum\NotificationType;
use Modules\User\Models\UserLoginHistory;
use Modules\User\Services\UserAgentParser;

final readonly class TrackLoginAction
{
    public function __construct(
        private UserAgentParser $parser,
    ) {}

    public function execute(User $user, Request $request): UserLoginHistory
    {
        $userAgent = $request->userAgent();
        $browser = $this->parser->detectBrowser($userAgent);
        $platform = $this->parser->detectPlatform($userAgent);

        $hasPriorLogins = UserLoginHistory::query()->where('user_id', $user->id)->exists();
        $seenThisDevice = UserLoginHistory::query()
            ->where('user_id', $user->id)
            ->where('browser', $browser)
            ->where('platform', $platform)
            ->exists();

        $history = UserLoginHistory::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $userAgent,
            'device_type' => $this->parser->detectDeviceType($userAgent),
            'browser' => $browser,
            'platform' => $platform,
            'logged_in_at' => now(),
        ]);

        // Alert the user only for a genuinely new device (not their very first login).
        if ($hasPriorLogins && ! $seenThisDevice) {
            NotifyAction::toUser(
                $user,
                __('user::user.notifications.new_device_title'),
                __('user::user.notifications.new_device_body', ['browser' => $browser, 'platform' => $platform]),
                NotificationType::Info,
                ['type' => 'new_device_login', 'browser' => $browser, 'platform' => $platform],
                ['database', 'fcm', 'mail'],
            );
        }

        return $history;
    }
}
