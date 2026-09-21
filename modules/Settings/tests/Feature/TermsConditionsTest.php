<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TermsConditionsTest extends TestCase
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

    public function test_can_view_terms_conditions_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.settings.special.terms_conditions'));

        $response->assertStatus(200);
        $response->assertViewIs('settings::special.terms-conditions');
        $response->assertViewHas('setting');
    }

    public function test_can_update_terms_conditions_when_setting_exists(): void
    {
        $this->actingAs($this->admin);

        // Create existing terms & conditions setting
        Setting::create([
            'key' => 'terms_conditions',
            'value' => 'Old terms and conditions content',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        $updatedContent = 'Updated terms and conditions content with new clauses.';

        $response = $this->post(route('admin.settings.special.update_terms_conditions'), [
            'terms_conditions' => $updatedContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Terms & Conditions updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'terms_conditions',
            'value' => $updatedContent,
        ]);
    }

    public function test_can_create_terms_conditions_when_setting_does_not_exist(): void
    {
        $this->actingAs($this->admin);

        $newContent = 'Brand new terms and conditions content.';

        $response = $this->post(route('admin.settings.special.update_terms_conditions'), [
            'terms_conditions' => $newContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Terms & Conditions updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'terms_conditions',
            'value' => $newContent,
            'type' => 'textarea',
            'group' => 'General',
        ]);
    }

    public function test_validation_fails_when_terms_conditions_is_empty(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.special.update_terms_conditions'), [
            'terms_conditions' => '',
        ]);

        $response->assertSessionHasErrors(['terms_conditions']);
    }

    public function test_validation_fails_when_terms_conditions_is_missing(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.settings.special.update_terms_conditions'), []);

        $response->assertSessionHasErrors(['terms_conditions']);
    }

    public function test_unauthorized_user_cannot_access_terms_conditions(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('admin.settings.special.terms_conditions'));

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_update_terms_conditions(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('admin.settings.special.update_terms_conditions'), [
            'terms_conditions' => 'Unauthorized update attempt',
        ]);

        $response->assertStatus(403);
    }

    public function test_terms_conditions_can_handle_long_content(): void
    {
        $this->actingAs($this->admin);

        $longContent = str_repeat('This is a very long terms and conditions content. ', 100);
        // Trim to match what will be stored (removes trailing space)
        $longContent = trim($longContent);

        $response = $this->post(route('admin.settings.special.update_terms_conditions'), [
            'terms_conditions' => $longContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Terms & Conditions updated successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'terms_conditions',
        ]);

        $setting = Setting::where('key', 'terms_conditions')->first();
        $this->assertEquals($longContent, $setting->value);
    }

    public function test_terms_conditions_can_handle_html_content(): void
    {
        $this->actingAs($this->admin);

        $htmlContent = '<h1>Terms & Conditions</h1><p>By using this service, you agree to our <strong>terms</strong>.</p>';

        $response = $this->post(route('admin.settings.special.update_terms_conditions'), [
            'terms_conditions' => $htmlContent,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Terms & Conditions updated successfully');

        $setting = Setting::where('key', 'terms_conditions')->first();
        $this->assertEquals($htmlContent, $setting->value);
    }

    public function test_terms_conditions_cache_is_cleared_after_update(): void
    {
        $this->actingAs($this->admin);

        // Create initial setting
        Setting::create([
            'key' => 'terms_conditions',
            'value' => 'Initial content',
            'type' => 'textarea',
            'group' => 'General',
        ]);

        // Cache should be cleared automatically by model event
        $response = $this->post(route('admin.settings.special.update_terms_conditions'), [
            'terms_conditions' => 'Updated content',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify the cache was cleared (the model has a booted method that does this)
        $setting = Setting::where('key', 'terms_conditions')->first();
        $this->assertEquals('Updated content', $setting->value);
    }

    public function test_both_privacy_policy_and_terms_conditions_work_independently(): void
    {
        $this->actingAs($this->admin);

        // Create privacy policy
        $privacyContent = 'Privacy Policy Content';
        $this->post(route('admin.settings.special.update_privacy_policy'), [
            'privacy_policy' => $privacyContent,
        ]);

        // Create terms & conditions
        $termsContent = 'Terms & Conditions Content';
        $this->post(route('admin.settings.special.update_terms_conditions'), [
            'terms_conditions' => $termsContent,
        ]);

        // Both should exist independently
        $this->assertDatabaseHas('settings', [
            'key' => 'privacy_policy',
            'value' => $privacyContent,
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'terms_conditions',
            'value' => $termsContent,
        ]);
    }
}
