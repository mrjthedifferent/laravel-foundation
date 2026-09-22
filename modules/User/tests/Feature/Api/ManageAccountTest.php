<?php

namespace Modules\User\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ManageAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Without an authorization check, any authenticated user could pass their
     * own password plus another user's user_id and delete that user's account.
     * This is the exact attack the fix in Api\UserController::manageAccount
     * closes.
     */
    public function test_a_plain_user_cannot_delete_another_users_account_via_user_id(): void
    {
        $attacker = User::factory()->create(['password' => 'password']);
        $victim = User::factory()->create();

        Sanctum::actingAs($attacker);

        $this->postJson('/api/v1/manage-account', [
            'action' => 'delete',
            'user_id' => $victim->id,
            'password' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    public function test_a_plain_user_cannot_reset_another_users_account_via_user_id(): void
    {
        $attacker = User::factory()->create(['password' => 'password']);
        $victim = User::factory()->create(['name' => 'Victim Name']);

        Sanctum::actingAs($attacker);

        $this->postJson('/api/v1/manage-account', [
            'action' => 'reset',
            'user_id' => $victim->id,
            'password' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->id, 'name' => 'Victim Name']);
    }

    public function test_a_user_with_delete_permission_can_manage_another_users_account_outside_production(): void
    {
        Permission::create(['name' => 'Delete User', 'guard_name' => 'web', 'module_name' => 'User']);
        $admin = User::factory()->create(['password' => 'password']);
        $admin->givePermissionTo('Delete User');
        $victim = User::factory()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/manage-account', [
            'action' => 'delete',
            'user_id' => $victim->id,
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
    }

    public function test_managing_another_users_account_is_blocked_in_production_even_with_permission(): void
    {
        app()['env'] = 'production';

        Permission::create(['name' => 'Delete User', 'guard_name' => 'web', 'module_name' => 'User']);
        $admin = User::factory()->create(['password' => 'password']);
        $admin->givePermissionTo('Delete User');
        $victim = User::factory()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/manage-account', [
            'action' => 'delete',
            'user_id' => $victim->id,
            'password' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    public function test_a_user_can_delete_their_own_account_without_any_special_permission(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/manage-account', [
            'action' => 'delete',
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_self_service_delete_still_requires_the_correct_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/manage-account', [
            'action' => 'delete',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
