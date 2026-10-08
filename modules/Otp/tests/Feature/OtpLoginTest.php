<?php

namespace Modules\Otp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Otp\Actions\GenerateOtpAction;
use Modules\Otp\Actions\VerifyOtpAction;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Models\VerificationCode;
use Modules\Otp\Notifications\SendVerificationCode;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private const string PHONE = '+8801712345678';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'settings.otp_login_enabled.value' => '1',
            'settings.otp_self_registration_enabled.value' => '0',
            'settings.otp_registration_role.value' => '',
            'settings.otp_digit_length.value' => 6,
        ]);
    }

    private function codeFor(string $phone): string
    {
        return (string) app(GenerateOtpAction::class)->execute($phone, ContactType::Phone)->plainCode;
    }

    // ── request ──────────────────────────────────────────────────────────────

    public function test_request_is_refused_when_otp_login_is_disabled(): void
    {
        config(['settings.otp_login_enabled.value' => '0']);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '01712345678'])
            ->assertForbidden();
    }

    public function test_request_sends_a_code_to_an_existing_user(): void
    {
        Notification::fake();
        User::factory()->create(['phone' => self::PHONE]);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '01712345678'])
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertSentTimes(SendVerificationCode::class, 1);
        $this->assertDatabaseHas('verification_codes', ['contact' => self::PHONE, 'contact_type' => 'phone']);
    }

    public function test_request_for_unknown_number_reports_success_but_sends_nothing(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '01712345678'])
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('verification_codes', 0);
    }

    public function test_request_sends_to_unknown_number_when_self_registration_is_on(): void
    {
        Notification::fake();
        config(['settings.otp_self_registration_enabled.value' => '1']);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => '01712345678'])->assertOk();

        Notification::assertSentTimes(SendVerificationCode::class, 1);
    }

    public function test_request_within_cooldown_is_throttled(): void
    {
        Notification::fake();
        User::factory()->create(['phone' => self::PHONE]);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => self::PHONE])->assertOk();
        $this->postJson('/api/v1/auth/otp/request', ['phone' => self::PHONE])->assertStatus(429);
    }

    public function test_request_rejects_an_invalid_phone(): void
    {
        $this->postJson('/api/v1/auth/otp/request', ['phone' => 'not-a-phone'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');
    }

    // ── verify ───────────────────────────────────────────────────────────────

    public function test_existing_user_signs_in_and_gets_a_token(): void
    {
        $user = User::factory()->create(['phone' => self::PHONE, 'phone_verified_at' => null]);
        $code = $this->codeFor(self::PHONE);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '01712345678',
            'code' => $code,
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_new_user', false)
            ->assertJsonStructure(['data' => ['token', 'user', 'is_new_user']]);

        $this->assertNotNull($user->fresh()->phone_verified_at);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'Pixel 8']);

        $this->withToken($response->json('data.token'))
            ->getJson('/api/v1/profile')
            ->assertOk();
    }

    public function test_code_cannot_be_used_twice(): void
    {
        User::factory()->create(['phone' => self::PHONE]);
        $code = $this->codeFor(self::PHONE);
        $payload = ['phone' => self::PHONE, 'code' => $code];

        $this->postJson('/api/v1/auth/otp/verify', $payload)->assertOk();
        $this->postJson('/api/v1/auth/otp/verify', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_wrong_code_is_rejected_and_counted(): void
    {
        User::factory()->create(['phone' => self::PHONE]);
        $code = $this->codeFor(self::PHONE);
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => self::PHONE, 'code' => $wrong])
            ->assertUnprocessable();

        $this->assertSame(1, VerificationCode::query()->first()->attempts);
    }

    public function test_code_is_locked_after_too_many_wrong_guesses(): void
    {
        User::factory()->create(['phone' => self::PHONE]);
        $code = $this->codeFor(self::PHONE);
        $wrong = $code === '000000' ? '111111' : '000000';

        // Guess through the action: the `auth` rate limiter would otherwise
        // stop the HTTP requests before the code's own attempt ceiling.
        for ($i = 0; $i < VerificationCode::MAX_ATTEMPTS; $i++) {
            app(VerifyOtpAction::class)->execute(self::PHONE, $wrong);
        }

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => self::PHONE, 'code' => $code])
            ->assertUnprocessable();
    }

    public function test_unknown_number_is_rejected_when_self_registration_is_off(): void
    {
        $code = $this->codeFor(self::PHONE);

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => self::PHONE, 'code' => $code])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['phone' => self::PHONE]);
    }

    public function test_unknown_number_is_registered_when_self_registration_is_on(): void
    {
        config([
            'settings.otp_self_registration_enabled.value' => '1',
            'settings.otp_registration_role.value' => 'Farmer',
        ]);
        Role::create(['name' => 'Farmer', 'guard_name' => 'web']);
        $code = $this->codeFor(self::PHONE);

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => self::PHONE,
            'code' => $code,
            'name' => 'রহিম',
        ])->assertOk()->assertJsonPath('data.is_new_user', true);

        $user = User::query()->wherePhone(self::PHONE)->firstOrFail();
        $this->assertSame('রহিম', $user->name);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue($user->hasRole('Farmer'));
    }

    public function test_inactive_user_cannot_sign_in(): void
    {
        User::factory()->create(['phone' => self::PHONE, 'is_active' => false]);
        $code = $this->codeFor(self::PHONE);

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => self::PHONE, 'code' => $code])
            ->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_whitelisted_number_signs_in_with_its_fixed_code(): void
    {
        Notification::fake();
        User::factory()->create(['phone' => self::PHONE]);
        OtpWhitelist::factory()->create([
            'recipient_type' => 'phone',
            'recipient' => self::PHONE,
            'fixed_otp' => '246810',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/auth/otp/request', ['phone' => self::PHONE])->assertOk();
        Notification::assertNothingSent();

        $this->postJson('/api/v1/auth/otp/verify', ['phone' => self::PHONE, 'code' => '246810'])
            ->assertOk();
    }
}
