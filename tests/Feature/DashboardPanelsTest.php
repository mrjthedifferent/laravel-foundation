<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;
use Mrj\Foundation\Services\Dashboard\DashboardCache;
use Mrj\Foundation\Services\Dashboard\HealthRegistry;
use Mrj\Foundation\Services\Dashboard\QuickActionRegistry;
use Mrj\Foundation\Support\HealthCheck;
use Mrj\Foundation\Support\QuickActionComposer;
use Mrj\Foundation\Tests\TestCase;
use Override;
use RuntimeException;

class DashboardPanelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FoundationSeeder::class);
    }

    private function admin(): User
    {
        return User::query()->where('is_super_admin', true)->first()
            ?? User::factory()->superAdmin()->create();
    }

    public function test_quick_actions_come_from_the_modules_and_are_filtered_by_permission(): void
    {
        $this->actingAs($this->admin());
        $labels = array_column(app(QuickActionRegistry::class)->all(), 'label');

        $this->assertContains(__('user::user.quick.add_user'), $labels);
        $this->assertContains(__('notification::notification.quick.send'), $labels);
        $this->assertContains(__('settings::settings.quick.general'), $labels);

        $this->actingAs(User::factory()->create());
        $this->assertSame([], app(QuickActionRegistry::class)->all());
    }

    public function test_quick_actions_keep_their_priority_order(): void
    {
        $this->actingAs($this->admin());

        $registry = new QuickActionRegistry;
        $registry->register(LateActions::class);
        $registry->register(EarlyActions::class);

        $this->assertSame(['early', 'late'], array_column($registry->all(), 'label'));
    }

    public function test_health_lists_what_needs_attention_before_what_is_fine(): void
    {
        $this->actingAs($this->admin());

        $registry = new HealthRegistry(app(DashboardCache::class));
        $registry->register(FineCheck::class);
        $registry->register(WarningCheck::class);
        $registry->register(BrokenCheck::class);

        $statuses = array_column($registry->all(), 'status');

        $this->assertSame([HealthCheck::FAIL, HealthCheck::WARN, HealthCheck::OK], $statuses);
    }

    public function test_a_check_that_throws_is_reported_as_failed_instead_of_breaking_the_page(): void
    {
        $this->actingAs($this->admin());

        $registry = new HealthRegistry(app(DashboardCache::class));
        $registry->register(ThrowingCheck::class);

        $result = $registry->all();

        $this->assertCount(1, $result);
        $this->assertSame(HealthCheck::FAIL, $result[0]['status']);
        $this->assertSame(__('foundation::foundation.dashboard.health_check_failed'), $result[0]['detail']);
    }

    public function test_health_hides_checks_the_viewer_may_not_see(): void
    {
        $this->actingAs(User::factory()->create());

        $registry = new HealthRegistry(app(DashboardCache::class));
        $registry->register(FineCheck::class);

        $this->assertSame([], $registry->all());
    }

    public function test_the_dashboard_shows_the_panels_the_viewer_may_use(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(__('foundation::foundation.dashboard.widget_quick_actions'))
            ->assertSee(__('foundation::foundation.dashboard.widget_health'))
            ->assertSee(__('foundation::foundation.dashboard.health_queue'))
            ->assertSee(__('user::user.widget.status_title'))
            ->assertSee(__('user::user.widget.heatmap_title'))
            ->assertSee('data-fd-dashboard', false);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(__('foundation::foundation.dashboard.widget_quick_actions'))
            ->assertDontSee(__('user::user.widget.status_title'));
    }

    public function test_the_error_report_and_backup_checks_report_their_state(): void
    {
        $this->actingAs($this->admin());

        $health = collect(app(HealthRegistry::class)->all());

        $reports = $health->firstWhere('label', __('errorreport::errorreport.health.label'));
        $this->assertSame(HealthCheck::OK, $reports['status']);

        $backups = $health->firstWhere('label', __('backupcleanup::backupcleanup.health.label'));
        $this->assertNotNull($backups);
        $this->assertContains($backups['status'], [HealthCheck::WARN, HealthCheck::FAIL]);
    }
}

final class EarlyActions extends QuickActionComposer
{
    #[Override]
    public function priority(): int
    {
        return 1;
    }

    public function actions(): array
    {
        return [['label' => 'early', 'icon' => 'ph-x', 'href' => '/a']];
    }
}

final class LateActions extends QuickActionComposer
{
    #[Override]
    public function priority(): int
    {
        return 99;
    }

    public function actions(): array
    {
        return [['label' => 'late', 'icon' => 'ph-x', 'href' => '/b']];
    }
}

final class FineCheck extends HealthCheck
{
    public function permissions(): array
    {
        return ['View Logs'];
    }

    public function check(): array
    {
        return ['status' => self::OK, 'label' => 'fine'];
    }
}

final class WarningCheck extends HealthCheck
{
    public function permissions(): array
    {
        return ['View Logs'];
    }

    public function check(): array
    {
        return ['status' => self::WARN, 'label' => 'warning'];
    }
}

final class BrokenCheck extends HealthCheck
{
    public function permissions(): array
    {
        return ['View Logs'];
    }

    public function check(): array
    {
        return ['status' => self::FAIL, 'label' => 'broken'];
    }
}

final class ThrowingCheck extends HealthCheck
{
    public function permissions(): array
    {
        return ['View Logs'];
    }

    public function check(): array
    {
        throw new RuntimeException('disk gone');
    }
}
