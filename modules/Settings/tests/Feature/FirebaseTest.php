<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FirebaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PreventRequestForgery::class,
        ]);

        $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Permission::create(['name' => 'Edit Special Setting', 'guard_name' => 'web', 'module_name' => 'Settings']);
        $this->adminRole->givePermissionTo('Edit Special Setting');

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_can_view_firebase_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings.special.firebase'));

        $response->assertStatus(200);
        $response->assertViewIs('settings::special.firebase');
        $response->assertViewHas(['firebaseCredentialsJson', 'firebaseProjectId']);
    }

    public function test_can_update_firebase_settings_when_settings_exist(): void
    {
        $this->actingAs($this->admin);

        Setting::create([
            'key' => 'firebase_credentials_json',
            'value' => '{}',
            'type' => 'textarea',
            'group' => 'Firebase',
        ]);
        Setting::create([
            'key' => 'firebase_project_id',
            'value' => 'old-project',
            'type' => 'text',
            'group' => 'Firebase',
        ]);

        $response = $this->post(route('admin.settings.special.update_firebase'), [
            'firebase_credentials_json' => '{"type":"service_account"}',
            'firebase_project_id' => 'my-firebase-project',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Firebase settings updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'firebase_credentials_json',
            'value' => '{"type":"service_account"}',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'firebase_project_id',
            'value' => 'my-firebase-project',
        ]);
    }

    public function test_can_create_firebase_settings_when_settings_do_not_exist(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.special.update_firebase'), [
            'firebase_credentials_json' => '{"type":"service_account","project_id":"new-project"}',
            'firebase_project_id' => 'new-firebase-project',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Firebase settings updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'firebase_credentials_json',
            'group' => 'Firebase',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'firebase_project_id',
            'value' => 'new-firebase-project',
            'group' => 'Firebase',
        ]);
    }

    public function test_can_update_firebase_with_empty_values(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.special.update_firebase'), [
            'firebase_credentials_json' => '',
            'firebase_project_id' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Firebase settings updated successfully');
    }

    public function test_unauthorized_user_cannot_access_firebase_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->get(route('admin.settings.special.firebase'));

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_update_firebase(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->post(route('admin.settings.special.update_firebase'), [
            'firebase_credentials_json' => '{}',
            'firebase_project_id' => 'hack',
        ]);

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_test_firebase_connection_without_credentials(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.settings.special.test_firebase'));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Firebase credentials JSON is not configured. Please save your credentials first.',
        ]);
    }

    public function test_authorized_user_can_test_firebase_connection_with_invalid_json(): void
    {
        $this->actingAs($this->admin);

        Setting::create([
            'key' => 'firebase_credentials_json',
            'value' => 'not-valid-json',
            'type' => 'textarea',
            'group' => 'Firebase',
        ]);

        $response = $this->postJson(route('admin.settings.special.test_firebase'));

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    public function test_unauthorized_user_cannot_test_firebase_connection(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->postJson(route('admin.settings.special.test_firebase'));

        $response->assertStatus(403);
    }
}
