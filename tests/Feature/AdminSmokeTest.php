<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Support\SidebarMenu;
use Mrj\Foundation\Tests\TestCase;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;

/**
 * A freshly seeded project: the seeded Super Admin signs in and every page the
 * sidebar offers opens.
 */
class AdminSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['sidebar.groups' => [
            'communications' => ['label' => 'Communications', 'icon' => 'ph-bell'],
            'administration' => ['label' => 'Administration', 'icon' => 'ph-shield'],
            'settings' => ['label' => 'Settings', 'icon' => 'ph-gear'],
            'imports' => ['label' => 'Import / Download Manager', 'icon' => 'ph-download', 'single' => true],
        ]]);

        $this->seed(FoundationSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', config('foundation.seed_admin.email'))->sole();
    }

    public function test_seeder_creates_roles_permissions_settings_and_the_super_admin(): void
    {
        $admin = $this->admin();

        $this->assertTrue($admin->isSuperAdmin());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertGreaterThan(20, Permission::count());
        $this->assertTrue($admin->can('View User'));
        $this->assertDatabaseHas('settings', ['key' => 'app_name']);
        $this->assertDatabaseHas('settings', ['key' => 'mail_notify_password_reset']);
        $this->assertDatabaseMissing('settings', ['key' => 'feature_leave']);

        // Safe to run again.
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

    public function test_every_module_model_used_in_a_morph_relation_has_an_alias(): void
    {
        foreach (['user', 'user_document', 'user_login_history', 'setting', 'notification', 'push_notification', 'download_import_manager', 'otp_whitelist'] as $alias) {
            $this->assertNotNull(Relation::getMorphedModel($alias), "morph alias [$alias] is missing");
        }

        $this->assertNull(Relation::getMorphedModel('memo'));
    }
}
