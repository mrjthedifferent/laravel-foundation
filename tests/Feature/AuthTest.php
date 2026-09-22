<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class, ThrottleRequests::class]);
        User::$lockedOut = [];
    }

    public function test_guest_pages_render(): void
    {
        // With no logo configured the app name is shown, never the placeholder image.
        $this->get(route('login'))->assertOk()->assertSee('name="login"', false)->assertDontSee('images/default.png');
        $this->get(route('password.request'))->assertOk()->assertSee('name="email"', false);
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_registration_is_closed(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->post('/register', ['email' => 'new@example.com'])->assertNotFound();
        $this->postJson('/api/v1/register', ['email' => 'new@example.com'])->assertNotFound();
    }

    public function test_user_signs_in_with_email_and_signs_out(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->post(route('login'), ['login' => 'Ada@Example.com', 'password' => 'secret-password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_wrong_password_inactive_and_locked_out_accounts_are_refused(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'nope'])->assertSessionHasErrors('login');
        $this->assertGuest();

        User::$lockedOut = [$user->id];
        $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'secret-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Access has ended.');
        $this->assertGuest();

        User::$lockedOut = [];
        $user->update(['is_active' => false]);
        $this->post(route('login'), ['login' => 'ada@example.com', 'password' => 'secret-password'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_an_unknown_phone_number_is_refused(): void
    {
        User::factory()->create(['password' => 'secret-password']);

        $this->post(route('login'), ['login' => '+8801700000000', 'password' => 'secret-password'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_reset_link_is_emailed_and_the_response_does_not_reveal_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $known = $this->post(route('password.email'), ['email' => 'ada@example.com']);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@example.com']);

        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertCount(1);

        $known->assertSessionHas('status')->assertSessionHasNoErrors();
        $unknown->assertSessionHas('status')->assertSessionHasNoErrors();
        $this->assertSame(session('status'), $known->getSession()->get('status'));
    }

    public function test_api_login_issues_a_token_and_profile_requires_it(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'secret-password']);

        $this->getJson('/api/v1/profile')->assertUnauthorized();

        $token = $this->postJson('/api/v1/login', ['id' => 'ada@example.com', 'password' => 'secret-password'])
            ->assertOk()
            ->json('data.token');

        $this->assertNotEmpty($token);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.email', 'ada@example.com');
    }
}
