<?php

namespace Modules\ActivityLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\ActivityLog\Jobs\ActivityLogExportJob;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class ActivityLogExportJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Created directly rather than by triggering a live audit event: whether
     * that even fires depends on the installed owen-it/laravel-auditing
     * version and the order tests run in (see SettingsCrudTest's audit
     * redaction test for the same issue) — irrelevant to what this test is
     * actually checking, which is the export job itself.
     */
    private function createAudit(User $user): Audit
    {
        return Audit::create([
            'user_type' => User::class,
            'user_id' => $user->id,
            'event' => 'updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
            'url' => 'http://localhost',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test/1.0',
            'tags' => null,
        ]);
    }

    public function test_it_writes_an_xlsx_file_and_marks_the_record_completed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->createAudit($user);

        $record = DownloadImportManager::create([
            'user_id' => $user->id,
            'title' => 'Activity Log Export',
            'type' => ImportType::Download,
            'status' => ImportStatus::Pending,
        ]);

        (new ActivityLogExportJob($record->id, ['format' => 'xlsx']))->handle();

        $record->refresh();

        $this->assertSame(ImportStatus::Completed, $record->status);
        $this->assertNotNull($record->url);
        Storage::disk('public')->assertExists($record->url);
    }

    public function test_it_fails_the_record_when_no_audits_match_the_filters(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->createAudit($user);

        $record = DownloadImportManager::create([
            'user_id' => $user->id,
            'title' => 'Activity Log Export',
            'type' => ImportType::Download,
            'status' => ImportStatus::Pending,
        ]);

        (new ActivityLogExportJob($record->id, ['format' => 'xlsx', 'search' => 'no-such-term-xyz']))->handle();

        $record->refresh();

        $this->assertSame(ImportStatus::Failed, $record->status);
        $this->assertSame('No data found for the selected filters.', $record->remarks);
    }
}
