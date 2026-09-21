<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PreventRequestForgery::class,
        ]);

        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Permission::create(['name' => 'Edit Special Setting', 'guard_name' => 'web', 'module_name' => 'Settings']);
        $role->givePermissionTo('Edit Special Setting');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_authorized_user_can_view_notification_settings_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings.special.notifications'));

        $response->assertStatus(200);
        $response->assertViewIs('settings::special.notifications');
        $response->assertViewHas('groups');
        $response->assertViewHas('channels');
    }

    public function test_unauthorized_user_cannot_view_notification_settings_page(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->get(route('admin.settings.special.notifications'))->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_update_notification_settings(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));

        $this->post(route('admin.settings.special.update_notifications'), [])->assertStatus(403);
    }

    public function test_update_persists_per_channel_toggles(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.special.update_notifications'), [
            'mail_notify_roles_changed' => '1',
            'push_notify_roles_changed' => '1',
            // inapp_notify_roles_changed omitted → off
        ]);

        $response->assertRedirect(route('admin.settings.special.notifications'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['key' => 'mail_notify_roles_changed', 'value' => '1', 'group' => 'Email Notifications']);
        $this->assertDatabaseHas('settings', ['key' => 'push_notify_roles_changed', 'value' => '1', 'group' => 'Notification Channels']);
        $this->assertDatabaseHas('settings', ['key' => 'inapp_notify_roles_changed', 'value' => '0', 'group' => 'Notification Channels']);
    }

    public function test_unsupported_and_locked_channel_keys_are_never_persisted(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.settings.special.update_notifications'), [
            'inapp_notify_otp_verification' => '1', // OTP has no in-app channel
            'sms_notify_otp_verification' => '1',   // OTP sms is locked on
        ])->assertRedirect();

        $this->assertDatabaseMissing('settings', ['key' => 'inapp_notify_otp_verification']);
        $this->assertDatabaseMissing('settings', ['key' => 'sms_notify_otp_verification']);
        $this->assertDatabaseHas('settings', ['key' => 'mail_notify_otp_verification', 'value' => '0']);
    }

    public function test_existing_mail_toggle_row_is_reused_not_duplicated(): void
    {
        Setting::create([
            'key' => 'mail_notify_password_reset',
            'group' => 'Email Notifications',
            'type' => 'boolean',
            'value' => '0',
            'is_visible' => false,
            'description' => 'Send the "Payslip Ready" email automatically',
        ]);

        $this->actingAs($this->admin);

        $this->post(route('admin.settings.special.update_notifications'), [
            'mail_notify_password_reset' => '1',
        ])->assertRedirect();

        $this->assertSame(1, Setting::where('key', 'mail_notify_password_reset')->count());
        $this->assertDatabaseHas('settings', ['key' => 'mail_notify_password_reset', 'value' => '1']);
    }

    public function test_update_clears_the_settings_cache(): void
    {
        Cache::put('app_settings', ['stale' => true]);

        $this->actingAs($this->admin);

        $this->post(route('admin.settings.special.update_notifications'), [])->assertRedirect();

        $this->assertNull(Cache::get('app_settings'));
    }

    public function test_old_email_notifications_url_redirects_to_new_page(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/settings/special/email-notifications')
            ->assertRedirect('/admin/settings/special/notifications');
    }
}
