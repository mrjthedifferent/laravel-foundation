<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\RolePermission\Database\Seeders\RolePermissionPermissionsSeeder;
use Modules\Settings\Models\Setting;
use Modules\Settings\Providers\SettingsServiceProvider;
use Mrj\Foundation\Contracts\TenancyContext;
use Mrj\Foundation\Enums\ModuleContext;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Support\MigrationPaths;
use Mrj\Foundation\Support\SidebarMenu;
use Mrj\Foundation\Support\Tenancy;
use Mrj\Foundation\Tests\Tenancy\FakeTenancyContext;
use Mrj\Foundation\Tests\Tenancy\MarkerMiddleware;
use Mrj\Foundation\Tests\Tenancy\TenantSwitched;
use Mrj\Foundation\Tests\TestCase;
use Nwidart\Modules\Activators\FileActivator;
use Override;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * foundation.tenancy enabled, with a fake tenancy library: a central fixture
 * module (Hub), a tenant fixture module (Ledger) and the foundation's own
 * universal modules. Config is set before the modules register, so this
 * overrides resolveApplicationConfiguration() like RoutingConfigurabilityTest.
 */
class TenancyHooksTest extends TestCase
{
    private const string CENTRAL = MarkerMiddleware::class.':central';

    private const string TENANT = MarkerMiddleware::class.':tenant';

    private const string UNIVERSAL = MarkerMiddleware::class.':universal';

    #[Override]
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('foundation.tenancy', [
            'enabled' => true,
            'middleware' => [
                'central' => [self::CENTRAL],
                'tenant' => [self::TENANT],
                'universal' => [self::UNIVERSAL],
            ],
            'central_domains' => ['central.test'],
            'context_changed_events' => [TenantSwitched::class],
        ]);

        $app['config']->set('modules.auto-discover.migrations', false);
        $app['config']->set('modules.scan', ['enabled' => true, 'paths' => [
            Foundation::modulesPath(),
            dirname(__DIR__).'/Tenancy/Modules',
        ]]);
        $app['config']->set('modules.activators', [
            'file' => [
                'class' => FileActivator::class,
                'statuses-file' => dirname(__DIR__).'/Tenancy/modules_statuses.json',
                'cache-key' => 'activator.installed.tenancy',
                'cache-lifetime' => 604800,
            ],
        ]);

        $app->instance(TenancyContext::class, new FakeTenancyContext);
    }

    private function enterTenant(?string $tenant): void
    {
        app(TenancyContext::class)->tenant = $tenant;
        Event::dispatch(new TenantSwitched);
    }

    /**
     * @return list<string>
     */
    private function middlewareOf(string $routeName): array
    {
        return Route::getRoutes()->getByName($routeName)->gatherMiddleware();
    }

    public function test_a_central_module_is_bound_to_the_central_domains_with_the_central_stack(): void
    {
        $route = Route::getRoutes()->getByName('hub.index');

        $this->assertSame('central.test', $route->getDomain());
        $this->assertContains(self::CENTRAL, $this->middlewareOf('hub.index'));
        $this->assertNotContains(self::TENANT, $this->middlewareOf('hub.index'));
    }

    public function test_a_tenant_module_gets_the_tenant_stack_on_web_and_api_routes(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('ledger.index')->getDomain());
        $this->assertContains(self::TENANT, $this->middlewareOf('ledger.index'));
        $this->assertContains('web', $this->middlewareOf('ledger.index'));

        $this->assertContains(self::TENANT, $this->middlewareOf('api.ledger.index'));
        $this->assertContains('api', $this->middlewareOf('api.ledger.index'));
        $this->assertSame('api/ledger', Route::getRoutes()->getByName('api.ledger.index')->uri());
    }

    public function test_universal_modules_and_the_dashboard_get_the_universal_stack(): void
    {
        foreach (['admin.users.index', 'admin.dashboard'] as $name) {
            $this->assertContains(self::UNIVERSAL, $this->middlewareOf($name), $name);
            $this->assertNull(Route::getRoutes()->getByName($name)->getDomain(), $name);
        }
    }

    public function test_tenant_tables_stay_out_of_the_central_database_and_are_listed_for_tenants(): void
    {
        $this->assertTrue(Schema::hasTable('hub_items'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('ledger_entries'));

        $paths = app(MigrationPaths::class);
        $tenant = $paths->for(ModuleContext::Tenant);
        $central = $paths->for(ModuleContext::Central);

        $this->assertContains(realpath(Foundation::path('database/migrations')), $tenant);
        $this->assertContains(realpath(Foundation::modulesPath().'/User/database/migrations'), $tenant);
        $this->assertContains(realpath(dirname(__DIR__).'/Tenancy/Modules/Ledger/database/migrations'), $tenant);
        $this->assertNotContains(realpath(dirname(__DIR__).'/Tenancy/Modules/Hub/database/migrations'), $tenant);

        $this->assertContains(realpath(dirname(__DIR__).'/Tenancy/Modules/Hub/database/migrations'), $central);
        $this->assertNotContains(realpath(dirname(__DIR__).'/Tenancy/Modules/Ledger/database/migrations'), $central);
    }

    public function test_the_sidebar_shows_only_the_modules_that_belong_where_the_app_is(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $labels = fn (): array => collect(app(SidebarMenu::class)->forUser($admin, null))
            ->flatMap(fn (array $parent): array => array_column($parent['items'], 'label'))
            ->all();

        $this->assertContains('Hub', $labels());
        $this->assertNotContains('Ledger', $labels());

        $this->enterTenant('acme');

        $this->assertContains('Ledger', $labels());
        $this->assertNotContains('Hub', $labels());
        $this->assertContains('Users', $labels());
    }

    public function test_permissions_are_seeded_by_module_context_and_entry_contexts(): void
    {
        $this->seed(RolePermissionPermissionsSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'View Hub']);
        $this->assertDatabaseHas('permissions', ['name' => 'View User']);
        $this->assertDatabaseMissing('permissions', ['name' => 'View Ledger']);

        Permission::query()->delete();
        $this->enterTenant('acme');
        $this->seed(RolePermissionPermissionsSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'View Ledger']);
        $this->assertDatabaseHas('permissions', ['name' => 'View User']);
        $this->assertDatabaseMissing('permissions', ['name' => 'Audit Ledger']);
        $this->assertDatabaseMissing('permissions', ['name' => 'View Hub']);
    }

    public function test_a_tenant_change_reapplies_that_tenants_settings_and_restores_the_rest(): void
    {
        $original = config('services.google.client_id');

        Setting::query()->create(['key' => 'google_client_id', 'group' => 'Social', 'value' => 'acme-client', 'type' => 'text']);
        $this->enterTenant('acme');

        $this->assertSame('acme-client', config('services.google.client_id'));
        $this->assertSame('acme-client', config('settings.google_client_id.value'));

        // The next tenant has no such setting: acme's value must not leak into it.
        Setting::query()->where('key', 'google_client_id')->delete();
        $this->enterTenant('globex');

        $this->assertSame($original, config('services.google.client_id'));
        $this->assertNull(config('settings.google_client_id'));
    }

    public function test_cache_keys_and_the_permission_cache_are_scoped_to_the_tenant(): void
    {
        $this->assertSame('app_settings', SettingsServiceProvider::cacheKey());

        $this->enterTenant('acme');

        $this->assertSame('tenant.acme.app_settings', SettingsServiceProvider::cacheKey());
        $this->assertSame('tenant.acme.anything', Tenancy::cacheKey('anything'));
        $this->assertStringEndsWith('.acme', app(PermissionRegistrar::class)->cacheKey);

        Cache::put(SettingsServiceProvider::cacheKey(), ['from acme']);
        $this->enterTenant(null);

        $this->assertSame('app_settings', SettingsServiceProvider::cacheKey());
        $this->assertNotSame(['from acme'], Cache::get(SettingsServiceProvider::cacheKey()));
        $this->assertStringEndsWith('.central', app(PermissionRegistrar::class)->cacheKey);
    }

    public function test_super_admin_cannot_be_granted_inside_a_tenant(): void
    {
        $this->enterTenant('acme');

        $this->artisan('foundation:super-admin', ['login' => 'owner@acme.test'])->assertFailed();
        $this->assertDatabaseMissing('users', ['email' => 'owner@acme.test']);
    }
}
