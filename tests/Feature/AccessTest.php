<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Mrj\Foundation\Http\Middleware\CheckUserIsActive;
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
            ->assertJsonPath('message', CheckUserIsActive::INACTIVE_MESSAGE);

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
}
