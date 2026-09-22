<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Notification\Support\NotificationToggleRegistry;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Http\Controllers\Controller;

/**
 * Dedicated management page for switching automatic notifications on/off
 * per channel (Email / In-App / Push / SMS). The matrix is driven by the
 * shared registry in Notification's app/Support/notification_toggles.php
 * so this page always reflects every gateable notification. The broadcast
 * channel follows the In-App toggle. Unsupported and locked channel cells
 * are never persisted.
 */
class NotificationSettingsController extends Controller
{
    /**
     * Channels shown as matrix columns, in display order.
     *
     * @return array<string, string>
     */
    private static function uiChannels(): array
    {
        return [
            'mail' => __('settings::settings.special_notifications.channel_mail'),
            'database' => __('settings::settings.special_notifications.channel_database'),
            'fcm' => __('settings::settings.special_notifications.channel_fcm'),
            'sms' => __('settings::settings.special_notifications.channel_sms'),
        ];
    }

    /**
     * Display the notification channel matrix, grouped by domain.
     */
    public function show(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);

        $groups = [];
        foreach (NotificationToggleRegistry::entries() as $entry) {
            $cells = [];
            foreach (array_keys(self::uiChannels()) as $channel) {
                $key = NotificationToggleRegistry::settingKey($entry, $channel);
                $cells[$channel] = [
                    'key' => $key,
                    'supported' => NotificationToggleRegistry::supports($entry, $channel),
                    'locked' => NotificationToggleRegistry::isLocked($entry, $channel),
                    'enabled' => (bool) config("settings.{$key}.value", true),
                ];
            }

            $groups[$entry['group']][] = [
                'label' => $entry['label'],
                'cells' => $cells,
            ];
        }

        return view('settings::special.notifications', [
            'groups' => $groups,
            'channels' => self::uiChannels(),
        ]);
    }

    /**
     * Persist all notification channel toggles.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        foreach (NotificationToggleRegistry::entries() as $entry) {
            foreach (array_keys(self::uiChannels()) as $channel) {
                if (! NotificationToggleRegistry::supports($entry, $channel)
                    || NotificationToggleRegistry::isLocked($entry, $channel)) {
                    continue;
                }

                $key = NotificationToggleRegistry::settingKey($entry, $channel);

                Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'group' => $channel === 'mail' ? 'Email Notifications' : 'Notification Channels',
                        'type' => 'boolean',
                        'value' => $request->boolean($key) ? '1' : '0',
                        'is_visible' => false,
                        // Stored in English; the settings pages translate it at display time.
                        'description' => NotificationToggleRegistry::description($entry, $channel),
                    ],
                );
            }
        }

        app(SettingsRepository::class)->forget();

        return redirect()->route('admin.settings.special.notifications')
            ->with('success', __('settings::settings.flash.notifications_updated'));
    }
}
