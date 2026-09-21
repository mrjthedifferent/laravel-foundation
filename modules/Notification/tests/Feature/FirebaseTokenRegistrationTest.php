<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Modules\Notification\Models\FirebaseToken;
use Tests\TestCase;

/**
 * The firebase-token endpoint upserts on the (token, device_id) unique key and transfers a device
 * to the current user. Re-registering a token already held by another user must reassign it (not
 * crash on the unique constraint), and rotating a device's token must not leave stale rows behind.
 */
class FirebaseTokenRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function register(User $user, string $token, string $deviceId): TestResponse
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/v1/firebase-token', [
            'token' => $token,
            'device_id' => $deviceId,
        ]);
    }

    public function test_same_token_and_device_reregistered_by_new_user_is_reassigned_not_duplicated(): void
    {
        $oldUser = User::factory()->create(['is_active' => true]);
        $newUser = User::factory()->create(['is_active' => true]);

        FirebaseToken::create([
            'user_id' => $oldUser->id,
            'device_id' => 'device-abc',
            'token' => 'fcm-token-xyz',
        ]);

        $this->register($newUser, 'fcm-token-xyz', 'device-abc')
            ->assertOk()
            ->assertJsonPath('success', true);

        // Exactly one row for this token+device, now owned by the new user.
        $this->assertSame(1, FirebaseToken::where('token', 'fcm-token-xyz')->where('device_id', 'device-abc')->count());
        $this->assertDatabaseHas('firebase_tokens', [
            'token' => 'fcm-token-xyz',
            'device_id' => 'device-abc',
            'user_id' => $newUser->id,
        ]);
        $this->assertDatabaseMissing('firebase_tokens', [
            'device_id' => 'device-abc',
            'user_id' => $oldUser->id,
        ]);
    }

    public function test_rotating_the_token_for_a_device_replaces_the_old_row(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        FirebaseToken::create([
            'user_id' => $user->id,
            'device_id' => 'device-abc',
            'token' => 'old-token',
        ]);

        $this->register($user, 'new-token', 'device-abc')->assertOk();

        $this->assertDatabaseMissing('firebase_tokens', ['token' => 'old-token']);
        $this->assertDatabaseHas('firebase_tokens', [
            'token' => 'new-token',
            'device_id' => 'device-abc',
            'user_id' => $user->id,
        ]);
        $this->assertSame(1, FirebaseToken::where('device_id', 'device-abc')->count());
    }

    public function test_registering_a_fresh_token_creates_a_row(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->register($user, 'brand-new', 'device-new')->assertOk();

        $this->assertDatabaseHas('firebase_tokens', [
            'token' => 'brand-new',
            'device_id' => 'device-new',
            'user_id' => $user->id,
        ]);
    }
}
