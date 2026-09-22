<?php

namespace Modules\ErrorReport\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ErrorReport\Models\ErrorReport;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ErrorReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private const array PERMISSIONS = ['View Error Report', 'Resolve Error Report', 'Delete Error Report'];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);

        foreach (self::PERMISSIONS as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web', 'module_name' => 'ErrorReport']);
        }

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->givePermissionTo(self::PERMISSIONS);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_index_lists_reports(): void
    {
        ErrorReport::factory()->create(['message' => 'Payment gateway timed out']);

        $this->actingAs($this->admin)
            ->get(route('admin.error-reports.index'))
            ->assertOk()
            ->assertSee('Payment gateway timed out');
    }

    public function test_index_searches_message_and_filters_by_resolution(): void
    {
        $open = ErrorReport::factory()->create(['message' => 'Open disk failure']);
        $closed = ErrorReport::factory()->resolved()->create(['message' => 'Closed disk failure']);
        $other = ErrorReport::factory()->create(['message' => 'Unrelated problem']);

        $ids = fn (array $query): array => $this->actingAs($this->admin)
            ->get(route('admin.error-reports.index', $query))
            ->assertOk()
            ->viewData('errorReports')
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([$open->id, $closed->id], $ids(['search' => 'disk failure']));
        $this->assertSame([$closed->id], $ids(['resolved' => '1']));
        $this->assertSame([$open->id, $other->id], $ids(['resolved' => '0']));
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        ErrorReport::factory()->create(['message' => 'Anything at all']);
        $literal = ErrorReport::factory()->create(['message' => 'Quota at 100% reached']);

        $found = $this->actingAs($this->admin)
            ->get(route('admin.error-reports.index', ['search' => '100%']))
            ->viewData('errorReports');

        $this->assertSame([$literal->id], $found->pluck('id')->all());

        // A bare "%" must not match everything.
        $percentOnly = $this->actingAs($this->admin)
            ->get(route('admin.error-reports.index', ['search' => '%']))
            ->viewData('errorReports');

        $this->assertSame([$literal->id], $percentOnly->pluck('id')->all());
    }

    public function test_show_displays_a_report(): void
    {
        $report = ErrorReport::factory()->create(['message' => 'Queue worker crashed']);

        $this->actingAs($this->admin)
            ->get(route('admin.error-reports.show', $report))
            ->assertOk()
            ->assertSee('Queue worker crashed');
    }

    public function test_viewing_requires_view_error_report(): void
    {
        $report = ErrorReport::factory()->create();
        $user = $this->userWith([]);

        $this->actingAs($user)->get(route('admin.error-reports.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.error-reports.show', $report))->assertForbidden();
    }

    public function test_resolve_marks_the_report_resolved(): void
    {
        $report = ErrorReport::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.error-reports.resolve', $report))
            ->assertRedirect(route('admin.error-reports.index'))
            ->assertSessionHas('success');

        $this->assertTrue($report->fresh()->isResolved());
    }

    public function test_resolve_requires_resolve_error_report(): void
    {
        $report = ErrorReport::factory()->create();

        $this->actingAs($this->userWith(['View Error Report']))
            ->post(route('admin.error-reports.resolve', $report))
            ->assertForbidden();

        $this->assertFalse($report->fresh()->isResolved());
    }

    public function test_destroy_deletes_the_report(): void
    {
        $report = ErrorReport::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.error-reports.destroy', $report))
            ->assertRedirect(route('admin.error-reports.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($report);
    }

    public function test_destroy_requires_delete_error_report(): void
    {
        $report = ErrorReport::factory()->create();

        $this->actingAs($this->userWith(['View Error Report', 'Resolve Error Report']))
            ->delete(route('admin.error-reports.destroy', $report))
            ->assertForbidden();

        $this->assertModelExists($report);
    }
}
