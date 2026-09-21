<?php

namespace Modules\User\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\ImportDownloadManager\Actions\UpdateImportRecordAction;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\User\Queries\UserQuery;
use Mpdf\Mpdf;
use Mrj\Foundation\Services\PDFService;
use Rap2hpoutre\FastExcel\FastExcel;

class UserExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected int $importDownloadManagerId,
        protected array $filters,
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
        set_time_limit(0);

        try {
            app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Processing);

            $users = UserQuery::make()
                ->withRelations(['roles'])
                ->filterByRole($this->filters['role'] ?? null)
                ->filterByStatus(isset($this->filters['is_active']) ? (bool) $this->filters['is_active'] : null)
                ->search($this->filters['search'] ?? null)
                ->orderByLatest()
                ->get();

            if ($users->isEmpty()) {
                app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Failed, 'No Data Found For Export');

                return;
            }

            $sl = 1;
            $exportData = $users->map(function (User $user) use (&$sl): array {
                return [
                    'SL' => $sl++,
                    'Name' => $user->name ?: '',
                    'Email' => $user->email ?: '',
                    'Phone' => $user->phone ?: '',
                    'Gender' => $user->gender ? ucfirst($user->gender->value) : '',
                    'Role' => $user->roles->pluck('name')->implode(', '),
                    'Account Status' => $user->is_active ? 'Active' : 'Inactive',
                    'Registration Date' => $user->created_at->format('d-M-Y h:i:s A'),
                ];
            })->toArray();

            $format = $this->filters['format'] ?? 'xlsx';
            $extension = match ($format) {
                'pdf' => 'pdf',
                'csv' => 'csv',
                default => 'xlsx'
            };

            Storage::makeDirectory('public/exports');
            $filePath = 'exports/user_list_'.time().'.'.$extension;
            $fullPath = storage_path('app/public/'.$filePath);

            if ($format === 'pdf') {
                $html = view('exports.pdf.generic', [
                    'title' => 'User List',
                    'headers' => array_keys($exportData[0]),
                    'data' => $exportData,
                ])->render();

                $mpdf = new Mpdf([
                    'tempDir' => PDFService::getMpdfTempDir(),
                    'fontDir' => PDFService::getMpdfFontsDirs(),
                    'fontdata' => PDFService::getMpdfFontData(),
                    'default_font' => PDFService::defaultFont(),
                    'orientation' => 'L',
                ]);
                $mpdf->WriteHTML($html);
                $mpdf->Output($fullPath, 'F');
            } else {
                (new FastExcel($exportData))->export($fullPath);
            }

            app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Completed, 'completed', $filePath);
        } catch (\Throwable $e) {
            app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Failed, $e->getMessage());
            Log::error('User export failed', ['error' => $e->getMessage()]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Failed, $exception->getMessage());
        Log::error('User export job failed', ['error' => $exception->getMessage()]);
    }
}
