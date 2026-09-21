<?php

namespace Modules\ErrorReport\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\MailerSecretCipher;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ErrorReportSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);

        Permission::create(['name' => 'Edit Error Report Settings', 'guard_name' => 'web', 'module_name' => 'ErrorReport']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->givePermissionTo('Edit Error Report Settings');
    }

    public function test_unauthorized_user_cannot_view_settings(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.error-reports.settings.index'))
            ->assertStatus(403);
    }

    /**
     * The Slack webhook URL and Telegram bot token are secrets: whoever holds
     * either can post into the team's alert channel or impersonate the bot.
     * They must be encrypted at rest, not stored the way a plain webhook URL
     * setting would be.
     */
    public function test_slack_webhook_and_telegram_token_are_stored_encrypted(): void
    {
        $this->actingAs($this->admin)->post(route('admin.error-reports.settings.update'), [
            'error_report_enabled' => true,
            'error_report_channels' => ['slack', 'telegram'],
            'error_report_slack_webhook' => 'https://hooks.slack.com/services/T00/B00/xxxxxxxx',
            'error_report_telegram_bot_token' => '123456:ABC-DEF',
            'error_report_telegram_chat_id' => '-100123',
            'error_report_throttle_minutes' => 60,
        ])->assertRedirect();

        $webhook = Setting::where('key', 'error_report_slack_webhook')->first();
        $token = Setting::where('key', 'error_report_telegram_bot_token')->first();
        $cipher = app(MailerSecretCipher::class);

        $this->assertSame('encrypted', $webhook->type);
        $this->assertTrue($cipher->isEncrypted($webhook->getRawOriginal('value')));
        $this->assertSame('https://hooks.slack.com/services/T00/B00/xxxxxxxx', $webhook->value);

        $this->assertSame('encrypted', $token->type);
        $this->assertTrue($cipher->isEncrypted($token->getRawOriginal('value')));
        $this->assertSame('123456:ABC-DEF', $token->value);

        // The chat ID is not a secret and stays in plain text.
        $this->assertDatabaseHas('settings', ['key' => 'error_report_telegram_chat_id', 'value' => '-100123']);
    }

    public function test_updating_settings_again_preserves_previously_saved_secrets(): void
    {
        $this->actingAs($this->admin)->post(route('admin.error-reports.settings.update'), [
            'error_report_enabled' => true,
            'error_report_channels' => ['slack'],
            'error_report_slack_webhook' => 'https://hooks.slack.com/services/T00/B00/xxxxxxxx',
            'error_report_throttle_minutes' => 60,
        ]);

        $this->actingAs($this->admin)->post(route('admin.error-reports.settings.update'), [
            'error_report_enabled' => true,
            'error_report_channels' => ['slack'],
            'error_report_slack_webhook' => 'https://hooks.slack.com/services/T00/B00/updated',
            'error_report_throttle_minutes' => 60,
        ]);

        $webhook = Setting::where('key', 'error_report_slack_webhook')->first();

        $this->assertSame('https://hooks.slack.com/services/T00/B00/updated', $webhook->value);
    }
}
