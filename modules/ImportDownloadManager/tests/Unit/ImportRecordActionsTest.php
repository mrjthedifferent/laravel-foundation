<?php

namespace Modules\ImportDownloadManager\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Modules\ImportDownloadManager\Actions\CreateImportRecordAction;
use Modules\ImportDownloadManager\Actions\DeleteImportFileAction;
use Modules\ImportDownloadManager\Actions\UpdateImportRecordAction;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Notifications\AppNotification;
use RuntimeException;
use Tests\TestCase;

class ImportRecordActionsTest extends TestCase
{
    use RefreshDatabase;

    // ── create ────────────────────────────────────────────────────────────────

    public function test_a_download_record_starts_pending_without_a_file(): void
    {
        $user = User::factory()->create();

        $id = app(CreateImportRecordAction::class)->execute($user, 'Users Export', ImportType::Download);

        $this->assertDatabaseHas('download_import_managers', [
            'id' => $id,
            'user_id' => $user->id,
            'title' => 'Users Export',
            'type' => ImportType::Download->value,
            'status' => ImportStatus::Pending->value,
            'url' => null,
        ]);
    }

    public function test_an_import_record_stores_the_uploaded_file_in_the_given_directory(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $id = app(CreateImportRecordAction::class)->execute(
            $user,
            'Users Upload',
            ImportType::Import,
            file: UploadedFile::fake()->create('users.xlsx', 4),
            directory: 'uploads/test',
        );

        $url = (string) DownloadImportManager::query()->findOrFail($id)->getAttribute('url');
        $this->assertStringStartsWith('uploads/test/', $url);
        Storage::disk('public')->assertExists($url);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_moving_to_processing_updates_the_record_without_notifying(): void
    {
        Notification::fake();
        $record = DownloadImportManager::factory()->create();

        app(UpdateImportRecordAction::class)->execute($record->id, ImportStatus::Processing);

        $this->assertDatabaseHas('download_import_managers', ['id' => $record->id, 'status' => ImportStatus::Processing->value]);
        Notification::assertNothingSent();
    }

    public function test_completion_saves_the_url_and_notifies_with_a_download_link(): void
    {
        Notification::fake();
        $record = DownloadImportManager::factory()->processing()->create();

        app(UpdateImportRecordAction::class)->execute($record->id, ImportStatus::Completed, 'All rows exported', 'exports/users.xlsx');

        $this->assertDatabaseHas('download_import_managers', [
            'id' => $record->id,
            'status' => ImportStatus::Completed->value,
            'remarks' => 'All rows exported',
            'url' => 'exports/users.xlsx',
        ]);

        Notification::assertSentTo(
            $record->user,
            AppNotification::class,
            fn (AppNotification $n): bool => $n->type === NotificationType::Export
                && $n->data['url'] === route('admin.download.import.manager.download', $record),
        );
    }

    public function test_updating_without_a_url_keeps_the_existing_one(): void
    {
        Notification::fake();
        $record = DownloadImportManager::factory()->import()->create(['url' => 'uploads/imports/keep.xlsx']);

        app(UpdateImportRecordAction::class)->execute($record->id, ImportStatus::Processing);

        $this->assertDatabaseHas('download_import_managers', ['id' => $record->id, 'url' => 'uploads/imports/keep.xlsx']);
    }

    public function test_failure_notifies_the_user_with_the_remarks(): void
    {
        Notification::fake();
        $record = DownloadImportManager::factory()->processing()->create();

        app(UpdateImportRecordAction::class)->execute($record->id, ImportStatus::Failed, 'Row 4: invalid email');

        $this->assertDatabaseHas('download_import_managers', ['id' => $record->id, 'status' => ImportStatus::Failed->value]);
        Notification::assertSentTo(
            $record->user,
            AppNotification::class,
            fn (AppNotification $n): bool => $n->type === NotificationType::Error && $n->body === 'Row 4: invalid email',
        );
    }

    public function test_updating_a_missing_record_throws(): void
    {
        $this->expectException(RuntimeException::class);

        app(UpdateImportRecordAction::class)->execute(999, ImportStatus::Completed);
    }

    // ── delete file ───────────────────────────────────────────────────────────

    public function test_delete_file_removes_it_from_whichever_disk_holds_it(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('exports/private.xlsx', 'data');
        Storage::disk('public')->put('uploads/public.xlsx', 'data');

        $action = app(DeleteImportFileAction::class);
        $action->execute(DownloadImportManager::factory()->completed('exports/private.xlsx')->create());
        $action->execute(DownloadImportManager::factory()->completed('uploads/public.xlsx')->create());

        Storage::disk('local')->assertMissing('exports/private.xlsx');
        Storage::disk('public')->assertMissing('uploads/public.xlsx');
    }

    public function test_delete_file_is_a_no_op_without_a_file(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('keep.xlsx', 'data');

        app(DeleteImportFileAction::class)->execute(DownloadImportManager::factory()->create(['url' => null]));
        app(DeleteImportFileAction::class)->execute(DownloadImportManager::factory()->completed('gone.xlsx')->create());

        Storage::disk('public')->assertExists('keep.xlsx');
    }
}
