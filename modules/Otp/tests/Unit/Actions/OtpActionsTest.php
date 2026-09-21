<?php

namespace Modules\Otp\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Otp\Actions\GenerateOtpAction;
use Modules\Otp\Actions\SendOtpAction;
use Modules\Otp\Actions\StoreOtpWhitelistAction;
use Modules\Otp\Actions\UpdateOtpWhitelistAction;
use Modules\Otp\Actions\VerifyOtpAction;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Models\VerificationCode;
use Modules\Otp\Notifications\SendVerificationCode;
use Tests\TestCase;

class OtpActionsTest extends TestCase
{
    use RefreshDatabase;

    // ── GenerateOtpAction ─────────────────────────────────────────────────────

    public function test_generate_creates_verification_code_record(): void
    {
        $code = app(GenerateOtpAction::class)->execute('user@example.com', ContactType::Email);

        $this->assertInstanceOf(VerificationCode::class, $code);
        $this->assertDatabaseHas('verification_codes', [
            'contact' => 'user@example.com',
            'contact_type' => ContactType::Email->value,
        ]);
    }

    public function test_generate_produces_correct_digit_length(): void
    {
        config(['settings.otp_digit_length.value' => 6]);

        $code = app(GenerateOtpAction::class)->execute('user@example.com', ContactType::Email);

        $this->assertEquals(6, strlen($code->code));
        $this->assertMatchesRegularExpression('/^\d+$/', $code->code);
    }

    public function test_generate_respects_dynamic_expiry_minutes(): void
    {
        config(['settings.otp_expiry_minutes.value' => 15]);

        $before = now()->addMinutes(14);
        $after = now()->addMinutes(16);

        $code = app(GenerateOtpAction::class)->execute('user@example.com', ContactType::Email);

        $this->assertTrue($code->expires_at->between($before, $after));
    }

    public function test_generate_uses_fixed_otp_for_whitelisted_contact(): void
    {
        OtpWhitelist::factory()->create([
            'recipient_type' => ContactType::Email->value,
            'recipient' => 'whitelisted@example.com',
            'fixed_otp' => '999999',
            'is_active' => true,
        ]);

        $code = app(GenerateOtpAction::class)->execute('whitelisted@example.com', ContactType::Email);

        $this->assertEquals('999999', $code->code);
    }

    // ── SendOtpAction ─────────────────────────────────────────────────────────

    public function test_send_dispatches_notification_for_non_whitelisted_contact(): void
    {
        Notification::fake();

        app(SendOtpAction::class)->execute('user@example.com', ContactType::Email);

        Notification::assertSentTimes(SendVerificationCode::class, 1);
    }

    public function test_send_skips_notification_for_whitelisted_contact(): void
    {
        Notification::fake();

        OtpWhitelist::factory()->create([
            'recipient_type' => ContactType::Email->value,
            'recipient' => 'whitelisted@example.com',
            'fixed_otp' => '111111',
            'is_active' => true,
        ]);

        app(SendOtpAction::class)->execute('whitelisted@example.com', ContactType::Email);

        Notification::assertNothingSent();
    }

    // ── VerifyOtpAction ───────────────────────────────────────────────────────

    public function test_verify_returns_true_and_marks_code_used_for_valid_code(): void
    {
        $record = VerificationCode::factory()->create([
            'contact' => 'user@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'is_verified' => false,
        ]);

        $result = app(VerifyOtpAction::class)->execute('user@example.com', '123456');

        $this->assertTrue($result);
        $this->assertTrue($record->fresh()->is_verified);
    }

    public function test_verify_returns_false_for_wrong_code(): void
    {
        VerificationCode::factory()->create([
            'contact' => 'user@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $result = app(VerifyOtpAction::class)->execute('user@example.com', '000000');

        $this->assertFalse($result);
    }

    public function test_verify_returns_false_for_expired_code(): void
    {
        VerificationCode::factory()->create([
            'contact' => 'user@example.com',
            'code' => '123456',
            'expires_at' => now()->subMinute(),
        ]);

        $result = app(VerifyOtpAction::class)->execute('user@example.com', '123456');

        $this->assertFalse($result);
    }

    public function test_verify_returns_false_for_already_used_code(): void
    {
        VerificationCode::factory()->create([
            'contact' => 'user@example.com',
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
            'is_verified' => true,
        ]);

        $result = app(VerifyOtpAction::class)->execute('user@example.com', '123456');

        $this->assertFalse($result);
    }

    // ── StoreOtpWhitelistAction ───────────────────────────────────────────────

    public function test_store_whitelist_creates_new_entry(): void
    {
        $entry = app(StoreOtpWhitelistAction::class)->execute([
            'recipient_type' => 'email',
            'recipient' => 'dev@example.com',
            'fixed_otp' => '123456',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(OtpWhitelist::class, $entry);
        $this->assertDatabaseHas('otp_whitelists', ['recipient' => 'dev@example.com']);
    }

    public function test_store_whitelist_returns_null_for_duplicate(): void
    {
        OtpWhitelist::factory()->create([
            'recipient_type' => 'email',
            'recipient' => 'dev@example.com',
        ]);

        $entry = app(StoreOtpWhitelistAction::class)->execute([
            'recipient_type' => 'email',
            'recipient' => 'dev@example.com',
            'fixed_otp' => '999999',
        ]);

        $this->assertNull($entry);
        $this->assertEquals(1, OtpWhitelist::where('recipient', 'dev@example.com')->count());
    }

    // ── UpdateOtpWhitelistAction ──────────────────────────────────────────────

    public function test_update_whitelist_updates_fields(): void
    {
        $whitelist = OtpWhitelist::factory()->create(['fixed_otp' => '111111']);

        $result = app(UpdateOtpWhitelistAction::class)->execute($whitelist, ['fixed_otp' => '222222']);

        $this->assertTrue($result);
        $this->assertEquals('222222', $whitelist->fresh()->fixed_otp);
    }

    public function test_update_whitelist_returns_false_on_duplicate_recipient(): void
    {
        OtpWhitelist::factory()->create([
            'recipient_type' => 'email',
            'recipient' => 'existing@example.com',
        ]);

        $whitelist = OtpWhitelist::factory()->create([
            'recipient_type' => 'email',
            'recipient' => 'other@example.com',
        ]);

        $result = app(UpdateOtpWhitelistAction::class)->execute($whitelist, [
            'recipient_type' => 'email',
            'recipient' => 'existing@example.com',
        ]);

        $this->assertFalse($result);
        $this->assertEquals('other@example.com', $whitelist->fresh()->recipient);
    }
}
