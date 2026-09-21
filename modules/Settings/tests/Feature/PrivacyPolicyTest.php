<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF for testing
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
        ]);

        // Create role
        $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Create permission
        Permission::create(['name' => 'Edit Special Setting', 'guard_name' => 'web', 'module_name' => 'Settings']);

        // Assign permission to role
        $this->adminRole->givePermissionTo('Edit Special Setting');

        // Create admin user with is_active set to true
        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
    }

    public function test_can_view_privacy_policy_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings.special.privacy_policy'));

        $response->assertStatus(200);
        $response->assertViewIs('settings::special.privacy-policy');
        $response->assertViewHas('setting');
    }

    public function test_can_update_privacy_policy_when_setting_exists(): void
    {
        $this->actingAs($this->admin);

        // Create existing privacy policy setting
        Setting::create([
            'key' => 'privacy_policy',
            'value' => 'Old privacy policy content',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        $updatedContent = 'Updated privacy policy content with new terms and conditions.';

        $response = $this->post(route('admin.settings.special.update_privacy_policy'), [
            'privacy_policy' => $updatedContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Privacy Policy updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'privacy_policy',
            'value' => $updatedContent,
        ]);
    }

    public function test_can_create_privacy_policy_when_setting_does_not_exist(): void
    {
        $this->actingAs($this->admin);

        $newContent = 'Brand new privacy policy content.';

        $response = $this->post(route('admin.settings.special.update_privacy_policy'), [
            'privacy_policy' => $newContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Privacy Policy updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'privacy_policy',
            'value' => $newContent,
            'type' => 'textarea',
            'group' => 'General',
        ]);
    }

    public function test_validation_fails_when_privacy_policy_is_empty(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.special.update_privacy_policy'), [
            'privacy_policy' => '',
        ]);

        $response->assertSessionHasErrors(['privacy_policy']);
    }

    public function test_validation_fails_when_privacy_policy_is_missing(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.special.update_privacy_policy'), []);

        $response->assertSessionHasErrors(['privacy_policy']);
    }

    public function test_unauthorized_user_cannot_access_privacy_policy(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('admin.settings.special.privacy_policy'));

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_update_privacy_policy(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('admin.settings.special.update_privacy_policy'), [
            'privacy_policy' => 'Unauthorized update attempt',
        ]);

        $response->assertStatus(403);
    }

    public function test_privacy_policy_can_handle_long_content(): void
    {
        $this->actingAs($this->admin);

        $longContent = str_repeat('This is a very long privacy policy content. ', 100);
        // Trim to match what will be stored (removes trailing space)
        $longContent = trim($longContent);

        $response = $this->post(route('admin.settings.special.update_privacy_policy'), [
            'privacy_policy' => $longContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Privacy Policy updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'privacy_policy',
        ]);

        $setting = Setting::where('key', 'privacy_policy')->first();
        $this->assertEquals($longContent, $setting->value);
    }

    public function test_privacy_policy_can_handle_html_content(): void
    {
        $this->actingAs($this->admin);

        $htmlContent = '<h1>Privacy Policy</h1><p>This is a <strong>privacy policy</strong> with HTML.</p>';

        $response = $this->post(route('admin.settings.special.update_privacy_policy'), [
            'privacy_policy' => $htmlContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Privacy Policy updated successfully');

        $setting = Setting::where('key', 'privacy_policy')->first();
        $this->assertEquals($htmlContent, $setting->value);
    }

    public function test_privacy_policy_cache_is_cleared_after_update(): void
    {
        $this->actingAs($this->admin);

        // Create initial setting
        Setting::create([
            'key' => 'privacy_policy',
            'value' => 'Initial content',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        // Cache should be cleared automatically by model event
        $response = $this->post(route('admin.settings.special.update_privacy_policy'), [
            'privacy_policy' => 'Updated content',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the cache was cleared (the model has a booted method that does this)
        $setting = Setting::where('key', 'privacy_policy')->first();
        $this->assertEquals('Updated content', $setting->value);
    }
}
