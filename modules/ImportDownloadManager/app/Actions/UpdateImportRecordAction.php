<?php

namespace Modules\ImportDownloadManager\Actions;

use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\Notification\Actions\NotifyAction;
use Modules\Notification\Enum\NotificationType;
use RuntimeException;

/**
 * Update the status, remarks and/or URL of an existing import/download record.
 *
 * When status becomes Completed or Failed, a notification is sent to the record's user.
 *
 * Usage:
 *   app(UpdateImportRecordAction::class)->execute($id, ImportStatus::Processing);
 *   app(UpdateImportRecordAction::class)->execute($id, ImportStatus::Completed, 'Done', 'exports/file.xlsx');
 *   app(UpdateImportRecordAction::class)->execute($id, ImportStatus::Failed, 'Out of memory');
 */
final readonly class UpdateImportRecordAction
{
    public function execute(
        int $id,
        ImportStatus $status,
        ?string $remarks = null,
        ?string $url = null,
    ): void {
        $record = DownloadImportManager::find($id);

        if ($record === null) {
            throw new RuntimeException("DownloadImportManager record [{$id}] not found.");
        }

        $record->status = $status;
        $record->remarks = $remarks;

        if ($url !== null) {
            $record->url = $url;
        }

        $record->save();

        if ($status === ImportStatus::Completed || $status === ImportStatus::Failed) {
            $this->notifyUser($record);
        }
    }

    private function notifyUser(DownloadImportManager $record): void
    {
        $record->loadMissing('user');
        $user = $record->user;

        if ($user === null) {
            return;
        }

        $title = $record->title ?? 'Import/Export';

        if ($record->status === ImportStatus::Completed) {
            $data = ['type' => 'export_ready'];
            if ($record->url !== null) {
                $data['url'] = route('admin.download.import.manager.download', $record);
            }
            NotifyAction::toUser(
                $user,
                "{$title} Complete",
                'Your file is ready for download.',
                NotificationType::Export,
                $data,
                channels: ['database', 'broadcast', 'mail'],
            );
        } else {
            NotifyAction::toUser(
                $user,
                "{$title} Failed",
                $record->remarks ?? 'An error occurred.',
                NotificationType::Error,
                channels: ['database', 'broadcast'],
            );
        }
    }
}
