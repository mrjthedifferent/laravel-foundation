<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SocialAuthTest extends TestCase
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

    public function test_can_view_social_auth_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings.special.social_auth'));

        $response->assertStatus(200);
        $response->assertViewIs('settings::special.social-auth');
        $response->assertViewHas('settings');
    }

    public function test_can_update_social_auth_settings(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.special.update_social_auth'), [
            'google_client_id' => 'google-id',
            'google_client_secret' => 'google-secret',
            'google_redirect_uri' => '/auth/google/callback',
            'github_client_id' => '',
            'github_client_secret' => '',
            'github_redirect_uri' => '/auth/github/callback',
            'apple_client_id' => '',
            'apple_client_secret' => '',
            'apple_redirect_uri' => '/auth/apple/callback',
            'apple_team_id' => '',
            'apple_key_id' => '',
            'apple_key_file' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Social Auth settings updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'google_client_id',
            'value' => 'google-id',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'google_client_secret',
            'value' => 'google-secret',
        ]);
    }

    public function test_test_google_auth_returns_400_when_not_configured(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.settings.special.test_google_auth'));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Google is not fully configured. Fill in all required fields for this provider (and save if testing saved values).',
        ]);
    }

    public function test_test_github_auth_returns_400_when_not_configured(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.settings.special.test_github_auth'));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'GitHub is not fully configured. Fill in all required fields for this provider (and save if testing saved values).',
        ]);
    }

    public function test_test_apple_auth_returns_400_when_not_configured(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('admin.settings.special.test_apple_auth'));

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Apple is not fully configured. Fill in all required fields for this provider (and save if testing saved values).',
        ]);
    }

    public function test_unauthorized_user_cannot_view_social_auth_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->get(route('admin.settings.special.social_auth'));

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_update_social_auth(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->post(route('admin.settings.special.update_social_auth'), [
            'google_client_id' => 'id',
            'google_client_secret' => 'secret',
            'google_redirect_uri' => '/callback',
            'github_client_id' => '',
            'github_client_secret' => '',
            'github_redirect_uri' => '',
            'apple_client_id' => '',
            'apple_client_secret' => '',
            'apple_redirect_uri' => '',
            'apple_team_id' => '',
            'apple_key_id' => '',
            'apple_key_file' => '',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_test_google_auth(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->postJson(route('admin.settings.special.test_google_auth'));

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_test_github_auth(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->postJson(route('admin.settings.special.test_github_auth'));

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_test_apple_auth(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->postJson(route('admin.settings.special.test_apple_auth'));

        $response->assertStatus(403);
    }

    public function test_test_google_auth_returns_success_when_configured(): void
    {
        $this->actingAs($this->admin);

        Setting::create(['key' => 'google_client_id', 'value' => 'test-id.apps.googleusercontent.com', 'type' => 'text', 'group' => 'Social Auth']);
        Setting::create(['key' => 'google_client_secret', 'value' => 'test-secret', 'type' => 'text', 'group' => 'Social Auth']);
        Setting::create(['key' => 'google_redirect_uri', 'value' => 'http://localhost/auth/google/callback', 'type' => 'text', 'group' => 'Social Auth']);

        $response = $this->postJson(route('admin.settings.special.test_google_auth'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['message']);
    }

    public function test_test_github_auth_returns_success_when_configured(): void
    {
        $this->actingAs($this->admin);

        Setting::create(['key' => 'github_client_id', 'value' => 'test-id', 'type' => 'text', 'group' => 'Social Auth']);
        Setting::create(['key' => 'github_client_secret', 'value' => 'test-secret', 'type' => 'text', 'group' => 'Social Auth']);
        Setting::create(['key' => 'github_redirect_uri', 'value' => 'http://localhost/auth/github/callback', 'type' => 'text', 'group' => 'Social Auth']);

        $response = $this->postJson(route('admin.settings.special.test_github_auth'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['message']);
    }
}
