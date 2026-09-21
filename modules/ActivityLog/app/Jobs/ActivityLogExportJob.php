<?php

namespace Modules\ActivityLog\Jobs;

use BackedEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\ActivityLog\Helpers\ActivityLogHelper;
use Modules\ActivityLog\Queries\ActivityLogQuery;
use Modules\ImportDownloadManager\Actions\UpdateImportRecordAction;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Mpdf\Mpdf;
use Mrj\Foundation\Services\PDFService;
use OwenIt\Auditing\Models\Audit;
use Rap2hpoutre\FastExcel\FastExcel;
use Throwable;

class ActivityLogExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $importDownloadManagerId,
        protected array $filters,
    ) {}

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        try {
            app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Processing);

            $filters = $this->filters;
            $exportData = [];
            $sl = 1;

            ActivityLogQuery::make()
                ->withUser()
                ->search($filters['search'] ?? null)
                ->filterByEvent($filters['event'] ?? null)
                ->filterByAuditableType($filters['auditable_type'] ?? null)
                ->filterByUserId($filters['user_id'] ?? null)
                ->filterByDateFrom($filters['date_from'] ?? null)
                ->filterByDateTo($filters['date_to'] ?? null)
                ->get()
                ->each(function (Audit $audit) use (&$exportData, &$sl): void {
                    $metaData = $audit->getMetadata();
                    $modifiedData = $audit->getModified();
                    $changes = [];

                    foreach ($modifiedData as $key => $value) {
                        if (is_array($value) && isset($value['old'], $value['new'])) {
                            $old = self::castToString($value['old']);
                            $new = self::castToString($value['new']);
                            $changes[] = ActivityLogHelper::titleCase($key).': '.$old.' → '.$new;
                        } else {
                            $changes[] = ActivityLogHelper::titleCase($key).': '.self::castToString($value);
                        }
                    }

                    $exportData[] = [
                        'SL' => $sl++,
                        'ID' => $audit->id,
                        'Event' => ucfirst($audit->event),
                        'Entity Type' => ActivityLogHelper::getModelName($audit->auditable_type),
                        'Entity ID' => $audit->auditable_id,
                        'Changes' => implode(', ', $changes),
                        'IP Address' => $metaData['audit_ip_address'] ?? 'N/A',
                        'URL' => $metaData['audit_url'] ?? 'N/A',
                        'User Agent' => $metaData['audit_user_agent'] ?? 'N/A',
                        'User' => $audit->user
                            ? "{$audit->user->name} (ID: {$audit->user->getKey()})"
                            : 'System',
                        'Date' => $audit->created_at->format('Y-m-d H:i:s'),
                    ];
                });

            if (empty($exportData)) {
                app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Failed, 'No data found for the selected filters.');

                return;
            }

            $format = $this->filters['format'] ?? 'xlsx';
            $extension = match ($format) {
                'pdf' => 'pdf',
                'csv' => 'csv',
                default => 'xlsx'
            };

            Storage::makeDirectory('public/exports');
            $filePath = 'exports/activity_logs_'.time().'.'.$extension;
            $fullPath = storage_path('app/public/'.$filePath);

            if ($format === 'pdf') {
                $html = view('exports.pdf.generic', [
                    'title' => 'Activity Logs',
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
        } catch (Throwable $e) {
            app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Failed, $e->getMessage());
            Log::error('Activity log export error: '.$e->getMessage());
        }
    }

    public function failed(Throwable $exception): void
    {
        app(UpdateImportRecordAction::class)->execute($this->importDownloadManagerId, ImportStatus::Failed, 'Job failed: '.$exception->getMessage());
        Log::error('Activity log export job failed: '.$exception->getMessage());
    }

    private static function castToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        // Backed enum (e.g. ImportStatus, ImportType, Gender, etc.)
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        // Unit enum or any object with __toString
        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : get_class($value);
        }

        return (string) $value;
    }
}
