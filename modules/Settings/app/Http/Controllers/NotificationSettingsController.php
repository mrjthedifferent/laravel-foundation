<?php

namespace Modules\Settings\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Support\NotificationToggleRegistry;
use Modules\Settings\Models\Setting;
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
     * @var array<string, string>
     */
    private const UI_CHANNELS = [
        'mail' => 'Email',
        'database' => 'In-App',
        'fcm' => 'Push',
        'sms' => 'SMS',
    ];

    /**
     * Per-channel setting descriptions (sprintf with the entry label).
     *
     * @var array<string, string>
     */
    private const DESCRIPTIONS = [
        'mail' => 'Send the "%s" email automatically',
        'database' => 'Deliver the "%s" in-app notification',
        'fcm' => 'Send the "%s" push notification',
        'sms' => 'Send the "%s" SMS',
    ];

    /**
     * Display the notification channel matrix, grouped by domain.
     */
    public function show(): Renderable
    {
        $this->authorize('editSpecial', Setting::class);

        $groups = [];
        foreach (NotificationToggleRegistry::entries() as $entry) {
            $cells = [];
            foreach (array_keys(self::UI_CHANNELS) as $channel) {
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
            'channels' => self::UI_CHANNELS,
        ]);
    }

    /**
     * Persist all notification channel toggles.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('editSpecial', Setting::class);

        try {
            foreach (NotificationToggleRegistry::entries() as $entry) {
                foreach (array_keys(self::UI_CHANNELS) as $channel) {
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
                            'description' => sprintf(self::DESCRIPTIONS[$channel], $entry['label']),
                        ],
                    );
                }
            }

            Cache::forget('app_settings');

            return redirect()->route('admin.settings.special.notifications')
                ->with('success', 'Notification settings updated successfully');
        } catch (\Exception $e) {
            Log::error('Notification settings update failed', ['error' => $e->getMessage()]);

            return back()->with('error', 'Failed to update notification settings');
        }
    }
}
