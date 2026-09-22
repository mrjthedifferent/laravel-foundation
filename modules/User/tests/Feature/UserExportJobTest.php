<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\User\Jobs\UserExportJob;
use Tests\TestCase;

class UserExportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_writes_an_xlsx_file_and_marks_the_record_completed(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        User::factory()->count(2)->create();

        $record = DownloadImportManager::create([
            'user_id' => $admin->id,
            'title' => 'User Export',
            'type' => ImportType::Download,
            'status' => ImportStatus::Pending,
        ]);

        (new UserExportJob($record->id, ['format' => 'xlsx']))->handle();

        $record->refresh();

        $this->assertSame(ImportStatus::Completed, $record->status);
        $this->assertNotNull($record->url);
        Storage::disk('public')->assertExists($record->url);
    }

    public function test_it_fails_the_record_when_no_users_match_the_filters(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $record = DownloadImportManager::create([
            'user_id' => $admin->id,
            'title' => 'User Export',
            'type' => ImportType::Download,
            'status' => ImportStatus::Pending,
        ]);

        (new UserExportJob($record->id, ['format' => 'xlsx', 'search' => 'no-such-term-xyz']))->handle();

        $record->refresh();

        $this->assertSame(ImportStatus::Failed, $record->status);
        $this->assertSame('No Data Found For Export', $record->remarks);
    }
}
