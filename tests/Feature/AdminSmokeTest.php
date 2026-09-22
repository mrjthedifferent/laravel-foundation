<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Support\SidebarMenu;
use Mrj\Foundation\Tests\TestCase;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;

/**
 * A freshly seeded project: a Super Admin (created as `foundation:super-admin`
 * does, with no role at all) signs in and every page the sidebar offers opens.
 */
class AdminSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The sidebar parents come from the package's own config/sidebar.php, as in a fresh install.
        $this->seed(FoundationSeeder::class);
    }

    private function admin(): User
    {
        return User::query()->where('is_super_admin', true)->first()
            ?? User::factory()->superAdmin()->create();
    }

    public function test_seeder_creates_roles_permissions_and_settings_but_no_user(): void
    {
        $this->assertSame(0, User::count());
        $this->assertGreaterThan(20, Permission::count());
        $this->assertTrue($this->admin()->can('View User'));
        $this->assertSame([], $this->admin()->getRoleNames()->all());
        $this->assertDatabaseHas('settings', ['key' => 'app_name']);
        $this->assertDatabaseHas('settings', ['key' => 'mail_notify_password_reset']);
        $this->assertDatabaseMissing('settings', ['key' => 'feature_leave']);

        // Safe to run again, and still creates nobody.
        $this->seed(FoundationSeeder::class);
        $this->assertSame(1, User::count());
    }

    public function test_every_menu_item_of_every_module_is_placed_in_a_known_sidebar_group(): void
    {
        foreach (Module::allEnabled() as $module) {
            foreach ((array) config(strtolower($module->getName()).'.menu', []) as $item) {
                $this->assertArrayHasKey($item['group'], config('sidebar.groups'), $module->getName().': '.$item['label']);

                if (isset($item['route'])) {
                    $this->assertTrue(Route::has($item['route']), $module->getName().': route '.$item['route']);
                }
            }
        }
    }

    /**
     * Widgets cache their data. A fresh Laravel 13 app sets `cache.serializable_classes`
     * to false, so anything but scalars and arrays comes back as an incomplete object and
     * the second visit to the dashboard crashes. The array store never serializes, so
     * this uses the file store.
     */
    public function test_the_dashboard_renders_from_a_cache_that_refuses_objects(): void
    {
        config(['cache.default' => 'file', 'cache.serializable_classes' => false]);
        app('cache')->forgetDriver('file');
        app('cache')->store('file')->flush();

        $admin = $this->admin();
        $admin->update(['name' => 'Audited Change']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Administration');
    }

    public function test_super_admin_can_open_every_sidebar_page(): void
    {
        $admin = $this->admin();

        $groups = app(SidebarMenu::class)->forUser($admin, null);

        $this->assertNotEmpty($groups);
        $opened = 0;

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $this->actingAs($admin)->get($item['href'])->assertOk();
                $opened++;
            }
        }

        $this->assertGreaterThanOrEqual(12, $opened);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Administration');
        $this->actingAs($admin)->get(route('admin.profile.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.show', $admin))->assertOk();
    }

    /**
     * An account whose password an admin reset must set its own before doing anything else.
     */
    public function test_a_user_flagged_for_a_password_change_must_change_it_before_using_the_app(): void
    {
        $admin = User::factory()->superAdmin()->create(['password' => '12345678']);
        $admin->forceFill(['must_change_password' => true])->save();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertRedirect(route('admin.profile.edit'));

        $this->withoutMiddleware([PreventRequestForgery::class])
            ->actingAs($admin)
            ->put(route('password.update'), [
                'current_password' => '12345678',
                'password' => 'a-new-strong-password',
                'password_confirmation' => 'a-new-strong-password',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin->fresh())->get(route('admin.dashboard'))->assertOk();
    }

    public function test_every_module_model_used_in_a_morph_relation_has_an_alias(): void
    {
        foreach (['user', 'user_document', 'user_login_history', 'setting', 'notification', 'push_notification', 'download_import_manager', 'otp_whitelist'] as $alias) {
            $this->assertNotNull(Relation::getMorphedModel($alias), "morph alias [$alias] is missing");
        }

        $this->assertNull(Relation::getMorphedModel('memo'));
    }
}
