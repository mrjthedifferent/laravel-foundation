<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $developer;

    private Role $developerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PreventRequestForgery::class,
        ]);

        $this->developerRole = Role::create(['name' => 'developer', 'guard_name' => 'web']);

        Permission::create(['name' => 'Developer Setting', 'guard_name' => 'web', 'module_name' => 'Settings']);
        Permission::create(['name' => 'Edit System Setting', 'guard_name' => 'web', 'module_name' => 'Settings']);

        $this->developerRole->givePermissionTo(['Developer Setting', 'Edit System Setting']);

        $this->developer = User::factory()->create(['is_active' => true]);
        $this->developer->assignRole('developer');
    }

    // --- manage ---

    public function test_can_view_manage_page(): void
    {
        $this->actingAs($this->developer);

        $response = $this->get(route('admin.settings.manage'));

        $response->assertStatus(200);
        $response->assertViewIs('settings::manage');
        $response->assertViewHas('settings');
    }

    public function test_unauthorized_user_cannot_view_manage_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $response = $this->get(route('admin.settings.manage'));

        $response->assertStatus(403);
    }

    // --- create ---

    public function test_can_view_create_setting_page(): void
    {
        $this->actingAs($this->developer);

        $response = $this->get(route('admin.settings.create'));

        $response->assertStatus(200);
        $response->assertViewIs('settings::create');
    }

    public function test_can_create_a_new_text_setting(): void
    {
        $this->actingAs($this->developer);

        $response = $this->post(route('admin.settings.store_new'), [
            'key' => 'test_setting',
            'group' => 'General',
            'type' => 'text',
            'description' => 'A test setting',
            'is_visible' => true,
            'is_required' => true,
            'value_text' => 'hello world',
        ]);

        $response->assertRedirect(route('admin.settings.manage'));
        $response->assertSessionHas('success', 'Setting created successfully');

        $this->assertDatabaseHas('settings', [
            'key' => 'test_setting',
            'group' => 'General',
            'type' => 'text',
        ]);
    }

    public function test_create_setting_fails_with_duplicate_key(): void
    {
        $this->actingAs($this->developer);

        Setting::create(['key' => 'existing_key', 'group' => 'General', 'type' => 'text', 'value' => 'v']);

        $response = $this->post(route('admin.settings.store_new'), [
            'key' => 'existing_key',
            'group' => 'General',
            'type' => 'text',
        ]);

        $response->assertSessionHasErrors(['key']);
    }

    public function test_create_setting_fails_with_missing_required_fields(): void
    {
        $this->actingAs($this->developer);

        $response = $this->post(route('admin.settings.store_new'), []);

        $response->assertSessionHasErrors(['key', 'group', 'type']);
    }

    public function test_create_setting_fails_with_invalid_type(): void
    {
        $this->actingAs($this->developer);

        $response = $this->post(route('admin.settings.store_new'), [
            'key' => 'test_key',
            'group' => 'General',
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    // --- edit/update ---

    public function test_can_view_edit_setting_page(): void
    {
        $this->actingAs($this->developer);

        $setting = Setting::create(['key' => 'edit_me', 'group' => 'General', 'type' => 'text', 'value' => 'old']);

        $response = $this->get(route('admin.settings.edit', $setting));

        $response->assertStatus(200);
        $response->assertViewIs('settings::edit');
        $response->assertViewHas('setting');
    }

    public function test_can_update_a_setting(): void
    {
        $this->actingAs($this->developer);

        $setting = Setting::create(['key' => 'update_me', 'group' => 'General', 'type' => 'text', 'value' => 'old']);

        $response = $this->put(route('admin.settings.update', $setting), [
            'group' => 'Updated Group',
            'type' => 'text',
            'description' => 'New description',
            'is_visible' => true,
            'is_required' => false,
            'value_text' => 'new value',
        ]);

        $response->assertRedirect(route('admin.settings.manage'));
        $response->assertSessionHas('success', 'Setting updated successfully');

        $this->assertDatabaseHas('settings', [
            'id' => $setting->id,
            'group' => 'Updated Group',
        ]);
    }

    public function test_update_setting_fails_with_missing_required_fields(): void
    {
        $this->actingAs($this->developer);

        $setting = Setting::create(['key' => 'update_me', 'group' => 'General', 'type' => 'text', 'value' => 'v']);

        $response = $this->put(route('admin.settings.update', $setting), []);

        $response->assertSessionHasErrors(['group', 'type']);
    }

    // --- destroy ---

    public function test_can_delete_a_setting(): void
    {
        $this->actingAs($this->developer);

        $setting = Setting::create(['key' => 'delete_me', 'group' => 'General', 'type' => 'text', 'value' => 'v']);

        $response = $this->delete(route('admin.settings.destroy', $setting));

        $response->assertRedirect(route('admin.settings.manage'));
        $response->assertSessionHas('success', 'Setting deleted successfully');

        $this->assertDatabaseMissing('settings', ['id' => $setting->id]);
    }

    public function test_unauthorized_user_cannot_delete_a_setting(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $setting = Setting::create(['key' => 'keep_me', 'group' => 'General', 'type' => 'text', 'value' => 'v']);

        $response = $this->delete(route('admin.settings.destroy', $setting));

        $response->assertStatus(403);
        $this->assertDatabaseHas('settings', ['id' => $setting->id]);
    }

    // --- bulk operations ---

    public function test_bulk_update_visibility(): void
    {
        $this->actingAs($this->developer);

        $s1 = Setting::create(['key' => 'bulk_s1', 'group' => 'G', 'type' => 'text', 'value' => '', 'is_visible' => true]);
        $s2 = Setting::create(['key' => 'bulk_s2', 'group' => 'G', 'type' => 'text', 'value' => '', 'is_visible' => true]);

        $response = $this->put(route('admin.settings.bulk_update'), [
            'action' => 'visibility',
            'ids' => [$s1->id, $s2->id],
            'visibility' => '0',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', ['id' => $s1->id, 'is_visible' => false]);
        $this->assertDatabaseHas('settings', ['id' => $s2->id, 'is_visible' => false]);
    }

    public function test_bulk_delete(): void
    {
        $this->actingAs($this->developer);

        $s1 = Setting::create(['key' => 'del_s1', 'group' => 'G', 'type' => 'text', 'value' => '']);
        $s2 = Setting::create(['key' => 'del_s2', 'group' => 'G', 'type' => 'text', 'value' => '']);

        $response = $this->delete(route('admin.settings.bulk_delete'), [
            'ids' => [$s1->id, $s2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('settings', ['id' => $s1->id]);
        $this->assertDatabaseMissing('settings', ['id' => $s2->id]);
    }
}
