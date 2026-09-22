<?php

namespace Modules\User\Jobs;

use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\User\Services\BulkUserRowProcessor;
use Mrj\Foundation\Contracts\ImportTracker;
use Rap2hpoutre\FastExcel\FastExcel;
use Throwable;

class UserBulkUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $importDownloadManagerId,
    ) {}

    public function handle(BulkUserRowProcessor $processor): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        try {
            app(ImportTracker::class)->processing($this->importDownloadManagerId);

            $url = app(ImportTracker::class)->filePath($this->importDownloadManagerId);
            $filePath = Storage::disk(config('foundation.storage.disk'))->path($url);

            $collection = (new FastExcel)->import($filePath)->toArray();

            if (! $collection) {
                app(ImportTracker::class)->fail(
                    $this->importDownloadManagerId,
                    __('user::user.errors.no_data_found_in_file')
                );

                return;
            }

            // User accounts are keyed by email; dedupe uploaded rows against existing emails.
            $existingEmails = User::whereNotNull('email')->pluck('email')->toArray();
            $errors = [];
            $rowNo = 1;

            foreach ($collection as $row) {
                $rowNo++;
                $error = $processor->process($row, $existingEmails, $rowNo);

                if ($error !== null) {
                    $errors[] = count($errors) + 1 .'. '.$error;
                }
            }

            // Plain text: the record's remarks are rendered with a raw echo, so no
            // HTML may be built here, only line breaks the view converts safely.
            $remarks = count($errors) > 0 ? implode("\n", $errors) : __('user::user.flash.job_completed');
            app(ImportTracker::class)->complete($this->importDownloadManagerId, $remarks);
        } catch (Exception $e) {
            Log::error('User bulk upload failed', ['error' => $e->getMessage()]);
            app(ImportTracker::class)->fail($this->importDownloadManagerId, $e->getMessage());
        }
    }

    public function failed(Throwable $exception): void
    {
        app(ImportTracker::class)->fail($this->importDownloadManagerId, __('user::user.errors.job_failed', ['message' => $exception->getMessage()]));
        Log::error('User bulk upload job failed', ['error' => $exception->getMessage()]);
    }
}
