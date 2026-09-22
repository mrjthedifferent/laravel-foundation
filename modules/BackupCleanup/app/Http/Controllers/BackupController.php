<?php

namespace Modules\BackupCleanup\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Modules\BackupCleanup\Actions\DeleteBackupAction;
use Modules\BackupCleanup\Actions\ListBackupsAction;
use Modules\BackupCleanup\Jobs\RunBackupJob;
use Modules\BackupCleanup\Jobs\RunCleanupJob;
use Modules\BackupCleanup\Models\Backup;
use Mrj\Foundation\Http\Controllers\Controller;
use RuntimeException;

class BackupController extends Controller
{
    public function __construct(
        private readonly ListBackupsAction $listBackupsAction,
        private readonly DeleteBackupAction $deleteBackupAction,
    ) {}

    /**
     * List all backup files.
     */
    public function index()
    {
        $this->authorize('viewAny', Backup::class);

        $files = $this->listBackupsAction->execute();

        return view('backupcleanup::index', compact('files'));
    }

    /**
     * Trigger a new database backup via a separate process.
     */
    public function store(): RedirectResponse
    {
        $this->authorize('create', Backup::class);

        RunBackupJob::dispatch();

        return redirect()->route('admin.backups.index')
            ->with('success', __('backupcleanup::backupcleanup.flash.queued'));
    }

    /**
     * Download a backup file.
     */
    public function download(string $filename)
    {
        $this->authorize('download', Backup::class);

        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupName = config('backup.backup.name', config('app.name'));
        $filePath = $backupName.'/'.$filename;
        $disk = Storage::disk($disk);

        if (! $disk->exists($filePath)) {
            return redirect()->route('admin.backups.index')
                ->with('error', __('backupcleanup::backupcleanup.flash.file_not_found'));
        }

        return $disk->download($filePath, $filename);
    }

    /**
     * Delete a specific backup file.
     */
    public function destroy(string $filename): RedirectResponse
    {
        $this->authorize('delete', Backup::class);

        try {
            $this->deleteBackupAction->execute($filename);

            return redirect()->route('admin.backups.index')
                ->with('success', __('backupcleanup::backupcleanup.flash.deleted'));
        } catch (RuntimeException $e) {
            return redirect()->route('admin.backups.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Run the cleanup command to remove old backups.
     */
    public function cleanup(): RedirectResponse
    {
        $this->authorize('cleanup', Backup::class);

        RunCleanupJob::dispatch();

        return redirect()->route('admin.backups.index')
            ->with('success', __('backupcleanup::backupcleanup.flash.cleanup_queued'));
    }
}
