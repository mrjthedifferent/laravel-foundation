<?php

namespace Modules\ImportDownloadManager\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DownloadImportManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([ValidateCsrfToken::class]);

        foreach (['Download Import Manager Management', 'Import Manager Data Download', 'Import Manager Data Delete'] as $perm) {
            Permission::updateOrCreate(['name' => $perm, 'guard_name' => 'web'], ['module_name' => 'ImportDownloadManager']);
        }

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->givePermissionTo(['Download Import Manager Management', 'Import Manager Data Download', 'Import Manager Data Delete']);
    }

    private function record(array $attrs = []): DownloadImportManager
    {
        return DownloadImportManager::create(array_merge([
            'user_id' => $this->user->id,
            'title' => 'Test Export',
            'type' => ImportType::Download,
            'status' => ImportStatus::Completed,
            'url' => null,
        ], $attrs));
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_user_can_view_index(): void
    {
        $this->record();

        $this->actingAs($this->user)
            ->get(route('admin.download.import.manager.index'))
            ->assertOk()
            ->assertViewIs('importdownloadmanager::index');
    }

    public function test_index_only_shows_current_users_records(): void
    {
        $other = User::factory()->create(['is_active' => true]);
        DownloadImportManager::create(['user_id' => $other->id, 'title' => 'Other', 'type' => ImportType::Download, 'status' => ImportStatus::Completed]);
        $this->record(['title' => 'Mine']);

        $response = $this->actingAs($this->user)->get(route('admin.download.import.manager.index'));

        $response->assertViewHas('downloadImports', fn ($p) => $p->total() === 1);
    }

    public function test_index_filters_by_status(): void
    {
        $this->record(['status' => ImportStatus::Completed]);
        $this->record(['status' => ImportStatus::Failed]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.download.import.manager.index', ['status' => 'failed']));

        $response->assertViewHas('downloadImports', fn ($p) => $p->total() === 1);
    }

    public function test_index_filters_by_type(): void
    {
        $this->record(['type' => ImportType::Download]);
        $this->record(['type' => ImportType::Import]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.download.import.manager.index', ['type' => 'import']));

        $response->assertViewHas('downloadImports', fn ($p) => $p->total() === 1);
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_completed_record(): void
    {
        $record = $this->record(['status' => ImportStatus::Completed]);

        $this->actingAs($this->user)
            ->delete(route('admin.download.import.manager.delete', $record))
            ->assertRedirect();

        $this->assertDatabaseMissing('download_import_managers', ['id' => $record->id]);
    }

    public function test_cannot_delete_pending_record(): void
    {
        $record = $this->record(['status' => ImportStatus::Pending]);

        $this->actingAs($this->user)
            ->delete(route('admin.download.import.manager.delete', $record))
            ->assertRedirect();

        $this->assertDatabaseHas('download_import_managers', ['id' => $record->id]);
    }

    public function test_cannot_delete_processing_record(): void
    {
        $record = $this->record(['status' => ImportStatus::Processing]);

        $this->actingAs($this->user)
            ->delete(route('admin.download.import.manager.delete', $record))
            ->assertRedirect();

        $this->assertDatabaseHas('download_import_managers', ['id' => $record->id]);
    }

    public function test_destroy_deletes_file_from_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('exports/test.xlsx', 'content');

        $record = $this->record(['status' => ImportStatus::Completed, 'url' => 'exports/test.xlsx']);

        $this->actingAs($this->user)
            ->delete(route('admin.download.import.manager.delete', $record));

        Storage::disk('public')->assertMissing('exports/test.xlsx');
    }

    // ── statusUpdate ──────────────────────────────────────────────────────────

    public function test_status_update_returns_correct_statuses(): void
    {
        $r1 = $this->record(['status' => ImportStatus::Processing]);
        $r2 = $this->record(['status' => ImportStatus::Completed]);

        $this->actingAs($this->user)
            ->postJson(route('admin.download.import.status.update'), ['ids' => [$r1->id, $r2->id]])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $r1->id, 'status' => 'processing'])
            ->assertJsonFragment(['id' => $r2->id, 'status' => 'completed']);
    }

    public function test_status_update_returns_empty_for_no_ids(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('admin.download.import.status.update'), ['ids' => []])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', []);
    }

    // ── download ──────────────────────────────────────────────────────────────

    public function test_user_can_download_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('exports/file.xlsx', 'content');

        $record = $this->record(['url' => 'exports/file.xlsx', 'status' => ImportStatus::Completed]);

        $this->actingAs($this->user)
            ->get(route('admin.download.import.manager.download', $record))
            ->assertOk();
    }

    public function test_download_redirects_when_file_missing(): void
    {
        Storage::fake('public');
        $record = $this->record(['url' => 'exports/missing.xlsx', 'status' => ImportStatus::Completed]);

        $this->actingAs($this->user)
            ->get(route('admin.download.import.manager.download', $record))
            ->assertRedirect();
    }

    // ── guest ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.download.import.manager.index'))->assertRedirect(route('login'));
    }
}
