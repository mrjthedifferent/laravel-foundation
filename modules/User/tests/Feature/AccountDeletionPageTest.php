<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mrj\Foundation\Support\TwoFactorAuthenticator;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * The public /delete-account page: anyone deletes their own account with their sign-in,
 * without the app (app stores require this link).
 */
class AccountDeletionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['phone' => '+8801711000000', 'password' => Hash::make('secret-pass-1')]);
    }

    public function test_the_page_is_public(): void
    {
        $this->get('/delete-account')->assertOk()
            ->assertSee('Delete your account')
            ->assertSee('This cannot be undone.');
        $this->assertSame(url('/delete-account'), route('account.delete'));
    }

    public function test_the_right_sign_in_deletes_the_account_and_its_tokens(): void
    {
        $user = $this->user();
        $user->createToken('app');

        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'secret-pass-1', 'confirm' => '1'])
            ->assertRedirect(route('account.delete'));

        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->get('/delete-account')->assertSee('Your account has been deleted.');
    }

    public function test_wrong_details_or_no_tick_delete_nothing(): void
    {
        $user = $this->user();

        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'wrong', 'confirm' => '1'])
            ->assertSessionHasErrors(['login' => 'These details do not match an account.']);
        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors('confirm');

        $this->assertModelExists($user);
    }

    public function test_a_two_factor_account_needs_its_code(): void
    {
        config(['foundation.two_factor.enabled' => true]);
        $user = $this->user([
            'two_factor_secret' => app(TwoFactorAuthenticator::class)->generateSecret(),
            'two_factor_confirmed_at' => now(),
        ]);
        $form = ['login' => '01711000000', 'password' => 'secret-pass-1', 'confirm' => '1'];

        $this->post('/delete-account', $form)->assertSessionHasErrors('two_factor_code');
        $this->assertModelExists($user);

        $engine = new Google2FA;
        $code = $engine->oathTotp((string) $user->two_factor_secret, $engine->getTimestamp());
        $this->post('/delete-account', $form + ['two_factor_code' => $code])->assertRedirect(route('account.delete'));
        $this->assertModelMissing($user);
    }

    public function test_a_super_admin_is_not_deleted_here(): void
    {
        $admin = $this->user(['is_super_admin' => true]);

        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'secret-pass-1', 'confirm' => '1'])
            ->assertSessionHasErrors('login');

        $this->assertModelExists($admin);
    }

    public function test_the_app_settings_api_gives_the_page_url(): void
    {
        $this->getJson('/api/'.config('foundation.routing.api_prefix').'/settings/app')
            ->assertJsonPath('data.account_deletion_url', url('/delete-account'));
    }
}
