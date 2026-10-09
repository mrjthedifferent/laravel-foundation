<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\User\Enum\DeletionStatus;
use Modules\User\Models\AccountDeletionRequest;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Support\TwoFactorAuthenticator;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * The public /delete-account page: anyone asks for their own account to be deleted with their
 * sign-in, without the app (app stores require this link). Same rules as in the app.
 */
class AccountDeletionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
    }

    protected function tearDown(): void
    {
        Foundation::flushAccountDeletionBlockers();

        parent::tearDown();
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['phone' => '+8801711000000', 'password' => Hash::make('secret-pass-1')]);
    }

    public function test_the_page_is_public(): void
    {
        $this->get('/delete-account')->assertOk()
            ->assertSee('Delete your account')
            ->assertSee('What we keep, without your name or contact details')
            ->assertSee('deleted 30 days after the request is accepted');
        $this->assertSame(url('/delete-account'), route('account.delete'));
    }

    public function test_the_right_sign_in_schedules_the_deletion_and_signs_out(): void
    {
        $user = $this->user();
        $user->createToken('app');

        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'secret-pass-1', 'confirm' => '1'])
            ->assertRedirect(route('account.delete'));

        $this->assertModelExists($user);
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $user->id, 'status' => 'scheduled', 'source' => 'web']);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->get('/delete-account')->assertSee('Your account will be deleted on '.now()->addDays(30)->toDateString());
    }

    public function test_with_automatic_deletion_off_the_request_waits_for_review(): void
    {
        config(['foundation.account_deletion.automatic' => false]);
        $user = $this->user();

        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'secret-pass-1', 'confirm' => '1'])
            ->assertRedirect(route('account.delete'));

        $this->assertSame(DeletionStatus::PendingReview, AccountDeletionRequest::query()->where('user_id', $user->id)->firstOrFail()->status);
        $this->get('/delete-account')->assertSee('Our team reviews it');
    }

    public function test_blockers_are_shown_and_nothing_is_requested(): void
    {
        Foundation::accountDeletionBlocker(fn (User $user) => 'Finish your open orders first.');
        $this->user();

        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'secret-pass-1', 'confirm' => '1'])
            ->assertSessionHas('deletion_blockers', ['Finish your open orders first.']);

        $this->assertDatabaseCount('account_deletion_requests', 0);
    }

    public function test_wrong_details_or_no_tick_request_nothing(): void
    {
        $this->user();

        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'wrong', 'confirm' => '1'])
            ->assertSessionHasErrors(['login' => 'These details do not match an account.']);
        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'secret-pass-1'])
            ->assertSessionHasErrors('confirm');

        $this->assertDatabaseCount('account_deletion_requests', 0);
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
        $this->assertDatabaseCount('account_deletion_requests', 0);

        $engine = new Google2FA;
        $code = $engine->oathTotp((string) $user->two_factor_secret, $engine->getTimestamp());
        $this->post('/delete-account', $form + ['two_factor_code' => $code])->assertRedirect(route('account.delete'));
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $user->id]);
    }

    public function test_a_super_admin_is_not_deleted_here(): void
    {
        $this->user(['is_super_admin' => true]);

        $this->post('/delete-account', ['login' => '01711000000', 'password' => 'secret-pass-1', 'confirm' => '1'])
            ->assertSessionHas('deletion_blockers');

        $this->assertDatabaseCount('account_deletion_requests', 0);
    }

    public function test_the_app_settings_api_gives_the_page_url(): void
    {
        $this->getJson('/api/'.config('foundation.routing.api_prefix').'/settings/app')
            ->assertJsonPath('data.account_deletion_url', url('/delete-account'));
    }
}
