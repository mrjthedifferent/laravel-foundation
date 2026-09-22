<?php

namespace Mrj\Foundation\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use Mrj\Foundation\Contracts\ImportTracker;
use Mrj\Foundation\Services\PDFService;
use Rap2hpoutre\FastExcel\FastExcel;
use Throwable;

/**
 * A queued export to xlsx, csv or pdf, tracked through a
 * DownloadImportManager record. A subclass supplies the rows (buildData()),
 * a title for the PDF header, and a filename prefix; this class owns the
 * shared shape: mark Processing, write the file to the configured disk,
 * mark Completed with its path, and mark Failed (with logging) on any error
 * or on job failure — including retries exhausted.
 *
 * Real $tries/$timeout replace the previous ini_set('memory_limit', '-1')/
 * set_time_limit(0), which had no ceiling at all; 30 minutes comfortably
 * covers a large export without leaving a runaway job unbounded.
 */
abstract class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 1800;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected int $importDownloadManagerId,
        protected array $filters,
    ) {}

    public function handle(): void
    {
        try {
            app(ImportTracker::class)->processing($this->importDownloadManagerId);

            $exportData = $this->buildData();

            if ($exportData === []) {
                app(ImportTracker::class)->fail($this->importDownloadManagerId, $this->emptyMessage());

                return;
            }

            $format = $this->filters['format'] ?? 'xlsx';
            $extension = match ($format) {
                'pdf' => 'pdf',
                'csv' => 'csv',
                default => 'xlsx'
            };

            $disk = config('foundation.storage.disk');
            $exportsPath = config('foundation.storage.exports_path');

            Storage::disk($disk)->makeDirectory($exportsPath);
            $filePath = $exportsPath.'/'.$this->filenamePrefix().'_'.time().'.'.$extension;
            $fullPath = Storage::disk($disk)->path($filePath);

            if ($format === 'pdf') {
                $html = view('exports.pdf.generic', [
                    'title' => $this->title(),
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

            app(ImportTracker::class)->complete($this->importDownloadManagerId, 'completed', $filePath);
        } catch (Throwable $e) {
            app(ImportTracker::class)->fail($this->importDownloadManagerId, $e->getMessage());
            Log::error($this->title().' export failed', ['error' => $e->getMessage()]);
        }
    }

    public function failed(Throwable $exception): void
    {
        app(ImportTracker::class)->fail($this->importDownloadManagerId, $exception->getMessage());
        Log::error($this->title().' export job failed', ['error' => $exception->getMessage()]);
    }

    /**
     * The rows to export, each a flat array of column label => value. An
     * empty array is treated as "nothing matched the filters" and fails the
     * job with emptyMessage() rather than writing a headerless file.
     *
     * @return list<array<string, mixed>>
     */
    abstract protected function buildData(): array;

    /**
     * Used as the PDF's heading and in log messages.
     */
    abstract protected function title(): string;

    /**
     * The exported file's name, before "_{timestamp}.{extension}".
     */
    abstract protected function filenamePrefix(): string;

    protected function emptyMessage(): string
    {
        return 'No data found for the selected filters.';
    }
}
