<?php

declare(strict_types=1);

namespace Modules\Otp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Modules\Otp\Models\VerificationCode;
use Modules\Otp\Notifications\SendVerificationCode;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OtpRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private const string PHONE = '+8801712345678';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'settings.otp_self_registration_enabled.value' => '1',
            'settings.otp_password_reset_enabled.value' => '1',
            'settings.otp_digit_length.value' => 6,
            'foundation.registration.roles' => [],
            'foundation.registration.require_terms' => false,
        ]);

        Notification::fake();
    }

    /**
     * The code delivered by the last SMS notification.
     */
    private function sentCode(): string
    {
        $code = null;
        Notification::assertSentTo(
            VerificationCode::query()->latest('id')->firstOrFail(),
            SendVerificationCode::class,
            function (SendVerificationCode $n, array $channels, object $notifiable) use (&$code): bool {
                preg_match('/(\d{6})$/', $n->toSms($notifiable), $m);
                $code = $m[1] ?? null;

                return $code !== null;
            },
        );

        return (string) $code;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'name' => 'রহিম উদ্দিন',
            'phone' => '01712345678',
            'email' => 'rahim@example.com',
            'password' => 'Secret-pass-2026',
        ], $overrides);
    }

    public function test_sign_up_creates_no_account_until_the_code_is_confirmed(): void
    {
        $this->postJson('/api/v1/auth/register/request', $this->form())->assertOk();

        $this->assertDatabaseMissing('users', ['phone' => self::PHONE]);
        Notification::assertSentTimes(SendVerificationCode::class, 1);
    }

    public function test_confirming_the_code_creates_a_verified_account_and_signs_in(): void
    {
        $this->postJson('/api/v1/auth/register/request', $this->form())->assertOk();

        $response = $this->postJson('/api/v1/auth/register/verify', [
            'phone' => self::PHONE,
            'code' => $this->sentCode(),
            'device_name' => 'Pixel',
        ])->assertCreated()->assertJsonStructure(['data' => ['token', 'user']]);

        $user = User::query()->wherePhone(self::PHONE)->firstOrFail();
        $this->assertSame('রহিম উদ্দিন', $user->name);
        $this->assertSame('rahim@example.com', $user->email);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue(Hash::check('Secret-pass-2026', $user->password));

        $this->withToken($response->json('data.token'))->getJson('/api/v1/profile')->assertOk();

        // And the password works for a normal sign-in.
        $this->postJson('/api/v1/login', ['id' => '8801712345678', 'password' => 'Secret-pass-2026'])->assertOk();
    }

    public function test_a_wrong_code_creates_nothing(): void
    {
        $this->postJson('/api/v1/auth/register/request', $this->form())->assertOk();
        $code = $this->sentCode();

        $this->postJson('/api/v1/auth/register/verify', ['phone' => self::PHONE, 'code' => $code === '000000' ? '111111' : '000000'])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('users', ['phone' => self::PHONE]);
    }

    public function test_a_phone_or_email_already_in_use_is_refused(): void
    {
        User::factory()->create(['phone' => self::PHONE]);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/auth/register/request', $this->form(['email' => 'taken@example.com']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'email']);
    }

    public function test_the_chosen_role_must_be_offered_and_is_assigned(): void
    {
        config(['foundation.registration.roles' => ['farmer', 'buyer']]);
        Role::create(['name' => 'farmer', 'guard_name' => 'web']);

        $this->postJson('/api/v1/auth/register/request', $this->form(['role' => 'admin']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->postJson('/api/v1/auth/register/request', $this->form(['role' => 'farmer']))->assertOk();
        $this->postJson('/api/v1/auth/register/verify', ['phone' => self::PHONE, 'code' => $this->sentCode()])->assertCreated();

        $this->assertTrue(User::query()->wherePhone(self::PHONE)->firstOrFail()->hasRole('farmer'));
    }

    public function test_terms_can_be_made_mandatory(): void
    {
        config(['foundation.registration.require_terms' => true]);

        $this->postJson('/api/v1/auth/register/request', $this->form())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('terms');

        $this->postJson('/api/v1/auth/register/request', $this->form(['terms' => true]))->assertOk();
    }

    public function test_sign_up_is_refused_when_disabled(): void
    {
        config(['settings.otp_self_registration_enabled.value' => '0']);

        $this->postJson('/api/v1/auth/register/request', $this->form())->assertForbidden();
    }

    public function test_password_reset_by_code_changes_the_password_and_signs_out_devices(): void
    {
        $user = User::factory()->create(['phone' => self::PHONE, 'password' => 'Old-pass-2026']);
        $user->createToken('old device');

        $this->postJson('/api/v1/auth/password/forgot', ['phone' => '01712345678'])->assertOk();

        $this->postJson('/api/v1/auth/password/reset', [
            'phone' => '01712345678',
            'code' => $this->sentCode(),
            'password' => 'New-pass-2026!',
            'password_confirmation' => 'New-pass-2026!',
        ])->assertOk();

        $this->assertTrue(Hash::check('New-pass-2026!', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_forgot_password_does_not_reveal_unknown_numbers(): void
    {
        $this->postJson('/api/v1/auth/password/forgot', ['phone' => '01712345678'])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_password_reset_with_a_wrong_code_changes_nothing(): void
    {
        $user = User::factory()->create(['phone' => self::PHONE, 'password' => 'Old-pass-2026']);
        $this->postJson('/api/v1/auth/password/forgot', ['phone' => self::PHONE])->assertOk();
        $code = $this->sentCode();

        $this->postJson('/api/v1/auth/password/reset', [
            'phone' => self::PHONE,
            'code' => $code === '000000' ? '111111' : '000000',
            'password' => 'New-pass-2026!',
            'password_confirmation' => 'New-pass-2026!',
        ])->assertUnprocessable();

        $this->assertTrue(Hash::check('Old-pass-2026', $user->fresh()->password));
    }
}
