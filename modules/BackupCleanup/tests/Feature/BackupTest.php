<?php

namespace Modules\BackupCleanup\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\BackupCleanup\Jobs\RunBackupJob;
use Modules\BackupCleanup\Jobs\RunCleanupJob;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);

        foreach (['View Backup', 'Create Backup', 'Download Backup', 'Delete Backup', 'Cleanup Backup'] as $perm) {
            Permission::updateOrCreate(['name' => $perm, 'guard_name' => 'web'], ['module_name' => 'BackupCleanup']);
        }

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->givePermissionTo(['View Backup', 'Create Backup', 'Download Backup', 'Delete Backup', 'Cleanup Backup']);
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_authenticated_user_with_permission_can_view_backups(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin)
            ->get(route('admin.backups.index'))
            ->assertOk()
            ->assertViewIs('backupcleanup::index');
    }

    public function test_user_without_permission_cannot_view_backups(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.backups.index'))
            ->assertForbidden();
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_user_with_permission_can_queue_backup_creation(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.backups.store'))
            ->assertRedirect(route('admin.backups.index'));

        Queue::assertPushed(RunBackupJob::class);
    }

    public function test_user_without_permission_cannot_create_backup(): void
    {
        Queue::fake();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('admin.backups.store'))
            ->assertForbidden();

        Queue::assertNotPushed(RunBackupJob::class);
    }

    // ── cleanup ───────────────────────────────────────────────────────────────

    public function test_user_with_permission_can_queue_cleanup(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.backups.cleanup'))
            ->assertRedirect(route('admin.backups.index'));

        Queue::assertPushed(RunCleanupJob::class);
    }

    public function test_user_without_permission_cannot_queue_cleanup(): void
    {
        Queue::fake();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('admin.backups.cleanup'))
            ->assertForbidden();

        Queue::assertNotPushed(RunCleanupJob::class);
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_user_with_permission_can_delete_backup(): void
    {
        Storage::fake('local');
        $backupName = config('backup.backup.name', config('app.name'));
        $filename = 'backup-2024-01-01-120000.zip';
        Storage::disk('local')->put("{$backupName}/{$filename}", 'fake-backup-content');

        $this->actingAs($this->admin)
            ->delete(route('admin.backups.destroy', ['filename' => $filename]))
            ->assertRedirect(route('admin.backups.index'));

        Storage::disk('local')->assertMissing("{$backupName}/{$filename}");
    }

    public function test_user_without_permission_cannot_delete_backup(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->delete(route('admin.backups.destroy', ['filename' => 'backup.zip']))
            ->assertForbidden();
    }
}
