<?php

namespace Mrj\Foundation\Tests\Unit;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\User\View\Composers\UserStatComposer;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use Mrj\Foundation\Services\Dashboard\StatRegistry;
use Mrj\Foundation\Support\StatComposer;
use Mrj\Foundation\Tests\TestCase;
use Override;
use Spatie\Permission\Models\Permission;

class StatComposerTest extends TestCase
{
    public function test_it_withholds_its_stats_from_a_viewer_without_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertSame([], app(UserStatComposer::class)->stats());
    }

    public function test_it_supplies_stats_to_a_viewer_with_permission(): void
    {
        $this->actingAsUserWhoMaySeeUsers();

        $stats = app(UserStatComposer::class)->stats();

        $this->assertCount(2, $stats);
        $this->assertSame(__('user::user.stat.active_users'), $stats[0]['label']);
        $this->assertArrayHasKey('value', $stats[0]);
    }

    public function test_it_caches_under_the_shared_dashboard_key(): void
    {
        $this->actingAsUserWhoMaySeeUsers();

        app(UserStatComposer::class)->stats();

        $this->assertNotNull(Cache::get(app(DashboardCache::class)->qualify('stat:user')));
    }

    public function test_the_registry_orders_by_priority_and_ignores_a_double_registration(): void
    {
        $this->actingAsUserWhoMaySeeUsers();

        $registry = new StatRegistry;
        $registry->register(LateStat::class);
        $registry->register(EarlyStat::class);
        $registry->register(LateStat::class);

        $this->assertSame(['early', 'late'], array_column($registry->all(), 'label'));
    }

    private function actingAsUserWhoMaySeeUsers(): void
    {
        // The package's permissions table requires a module name.
        Permission::query()->firstOrCreate(
            ['name' => 'View User', 'guard_name' => 'web'],
            ['module_name' => 'User'],
        );
        $user = User::factory()->create();
        $user->givePermissionTo('View User');
        $this->actingAs($user);
    }
}

/** @internal */
final class EarlyStat extends StatComposer
{
    #[Override]
    public function priority(): int
    {
        return 1;
    }

    #[Override]
    protected function permissions(): array
    {
        return ['View User'];
    }

    #[Override]
    protected function key(): string
    {
        return 'test-early';
    }

    #[Override]
    protected function build(): array
    {
        return [['label' => 'early', 'value' => '1', 'icon' => 'ph-users']];
    }
}

/** @internal */
final class LateStat extends StatComposer
{
    #[Override]
    public function priority(): int
    {
        return 99;
    }

    #[Override]
    protected function permissions(): array
    {
        return ['View User'];
    }

    #[Override]
    protected function key(): string
    {
        return 'test-late';
    }

    #[Override]
    protected function build(): array
    {
        return [['label' => 'late', 'value' => '2', 'icon' => 'ph-users']];
    }
}
