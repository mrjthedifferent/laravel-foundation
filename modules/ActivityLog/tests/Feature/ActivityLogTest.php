<?php

namespace Modules\ActivityLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ActivityLog\Models\SmsLog;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([ValidateCsrfToken::class]);

        foreach (['View Activity Log', 'Delete Activity Log', 'Export Activity Log', 'View SMS Log', 'Delete SMS Log'] as $perm) {
            Permission::updateOrCreate(['name' => $perm, 'guard_name' => 'web'], ['module_name' => 'ActivityLog']);
        }

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->givePermissionTo(['View Activity Log', 'Delete Activity Log', 'Export Activity Log', 'View SMS Log', 'Delete SMS Log']);
    }

    private function createAudit(): Audit
    {
        return Audit::create([
            'user_type' => User::class,
            'user_id' => $this->admin->id,
            'event' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => 1,
            'old_values' => '{}',
            'new_values' => '{}',
            'url' => 'http://localhost',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test/1.0',
            'tags' => null,
        ]);
    }

    // ── Activity Log ──────────────────────────────────────────────────────────

    public function test_authenticated_user_with_permission_can_view_activity_logs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertViewIs('activitylog::index');
    }

    public function test_unauthenticated_user_cannot_view_activity_logs(): void
    {
        $this->get(route('admin.activity-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_activity_logs(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.activity-logs.index'))
            ->assertForbidden();
    }

    public function test_user_with_permission_can_delete_activity_log(): void
    {
        $audit = $this->createAudit();

        $this->actingAs($this->admin)
            ->delete(route('admin.activity-logs.destroy', $audit->id))
            ->assertRedirect(route('admin.activity-logs.index'));

        $this->assertDatabaseMissing('audits', ['id' => $audit->id]);
    }

    public function test_user_without_permission_cannot_delete_activity_log(): void
    {
        $audit = $this->createAudit();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->delete(route('admin.activity-logs.destroy', $audit->id))
            ->assertForbidden();
    }

    // ── SMS Log ───────────────────────────────────────────────────────────────

    public function test_user_with_permission_can_view_sms_logs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.sms-logs.index'))
            ->assertOk()
            ->assertViewIs('activitylog::sms-logs.index');
    }

    public function test_user_without_permission_cannot_view_sms_logs(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.sms-logs.index'))
            ->assertForbidden();
    }

    public function test_user_with_permission_can_delete_sms_log(): void
    {
        $smsLog = SmsLog::create([
            'phone' => '1234567890',
            'message' => 'Test message',
            'status' => 'sent',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.sms-logs.destroy', $smsLog->id))
            ->assertRedirect(route('admin.sms-logs.index'));

        $this->assertDatabaseMissing('sms_logs', ['id' => $smsLog->id]);
    }
}
