<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\ErrorReport\Services\ErrorReporterService;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Contracts\ErrorReporter;
use Mrj\Foundation\Contracts\FileStorage;
use Mrj\Foundation\Contracts\ImportTracker;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Tests\TestCase;
use RuntimeException;

class ContractBindingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_reporter_is_bound_to_the_real_implementation_when_error_report_module_is_installed(): void
    {
        $this->assertInstanceOf(
            ErrorReporterService::class,
            app(ErrorReporter::class)
        );

        // Never throws even for a disabled-by-default reporter.
        app(ErrorReporter::class)->capture(new RuntimeException('test'));
    }

    public function test_file_storage_round_trips_through_the_default_local_implementation(): void
    {
        Storage::fake('public');

        $path = app(FileStorage::class)->uploadFile(UploadedFile::fake()->image('a.jpg'), directory: 'test');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertTrue(app(FileStorage::class)->fileExists($path));
    }

    public function test_settings_repository_get_and_fresh_agree_and_forget_clears_the_cache(): void
    {
        Setting::create(['key' => 'site_name', 'group' => 'G', 'type' => 'text', 'value' => 'Acme']);

        $repository = app(SettingsRepository::class);

        $this->assertSame('Acme', $repository->get('site_name'));
        $this->assertSame('Acme', $repository->fresh('site_name'));

        $repository->forget();
        $this->assertSame('Acme', $repository->all()[0]['value'] ?? null);
    }

    public function test_import_tracker_drives_a_record_through_its_full_lifecycle(): void
    {
        $user = User::factory()->create();
        $tracker = app(ImportTracker::class);

        $id = $tracker->start($user, 'Test Export', ImportType::Download);
        $tracker->processing($id);
        $tracker->complete($id, 'done', 'exports/file.xlsx');

        $this->assertSame(
            ImportStatus::Completed,
            DownloadImportManager::find($id)->status
        );
    }
}
