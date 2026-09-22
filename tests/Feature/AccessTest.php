<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Laravel\Sanctum\Sanctum;
use Mrj\Foundation\Tests\TestCase;

class AccessTest extends TestCase
{
    public function test_inactive_user_is_logged_out_of_the_web(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_inactive_user_loses_api_tokens(): void
    {
        $user = User::factory()->inactive()->create();
        $user->createToken('device');
        $user->deviceTokens()->create(['token' => 'push-token']);

        Sanctum::actingAs($user);

        $this->getJson('/api/ping')
            ->assertForbidden()
            ->assertJsonPath('message', __('foundation::foundation.auth.inactive'));

        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, $user->deviceTokens()->count());
    }

    public function test_active_user_passes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/ping')->assertOk()->assertJson(['pong' => true]);
    }

    public function test_user_gets_a_uuid_and_a_normalized_email(): void
    {
        $user = User::factory()->create(['email' => ' Mixed@Example.COM ']);

        $this->assertNotEmpty($user->uuid);
        $this->assertSame('mixed@example.com', $user->email);
    }

    /**
     * must_change_password is deliberately not mass-assignable (it must never
     * be settable through a user-editable form), so tests set it with
     * forceFill(), the same way the seeder and password-reset actions do.
     */
    private function userWhoMustChangePassword(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        return $user;
    }

    public function test_a_user_who_must_change_their_password_is_redirected_away_from_other_pages(): void
    {
        $this->actingAs($this->userWhoMustChangePassword())
            ->get('/admin/dashboard')
            ->assertRedirect(route('admin.profile.edit'));
    }

    public function test_a_user_who_must_change_their_password_can_still_reach_the_profile_and_logout_pages(): void
    {
        $user = $this->userWhoMustChangePassword();

        $this->withoutMiddleware([PreventRequestForgery::class]);

        $this->actingAs($user)->get(route('admin.profile.edit'))->assertOk();
        $this->actingAs($user)->post(route('logout'))->assertRedirect();
    }

    public function test_a_user_who_must_change_their_password_is_forbidden_on_the_api(): void
    {
        Sanctum::actingAs($this->userWhoMustChangePassword());

        $this->getJson('/api/ping')
            ->assertForbidden()
            ->assertJsonPath('message', 'You must set a new password before continuing.');
    }
}
