<?php

namespace Mrj\Foundation\Services;

use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use RuntimeException;
use Throwable;

class PDFService
{
    /**
     * Return a writable temp directory for Mpdf (inside storage so web server can write).
     * Prevents "mkdir(): Permission denied" on Ubuntu when www-data cannot write to storage/app.
     * Uses framework/cache first (usually writable); pre-creates the mpdf subdir mPDF needs.
     */
    public static function getMpdfTempDir(): string
    {
        $candidates = [
            storage_path('framework/cache/mpdf-tmp'),
            storage_path('app/mpdf-tmp'),
        ];

        foreach ($candidates as $path) {
            try {
                File::ensureDirectoryExists($path, 0775);
                File::ensureDirectoryExists($path.'/mpdf', 0775);
                if (is_writable($path)) {
                    return $path;
                }
            } catch (Throwable) {
                continue;
            }
        }

        throw new RuntimeException(
            'PDF temp directory could not be created. On Ubuntu, ensure storage is writable by the web server: '
                .'sudo chown -R www-data:www-data storage && sudo chmod -R 775 storage'
        );
    }

    /**
     * mPDF's own fonts, plus the project's resources/fonts directory when it has one.
     */
    public static function getMpdfFontsDirs(): array
    {
        $fontDirs = (new ConfigVariables)->getDefaults()['fontDir'];

        return array_merge($fontDirs, array_filter([base_path('resources/fonts')], 'is_dir'));
    }

    /**
     * mPDF's font table plus the fonts listed in config('foundation.pdf.fonts')
     * whose files the project ships in resources/fonts.
     */
    public static function getMpdfFontData(): array
    {
        $fontData = (new FontVariables)->getDefaults()['fontdata'];

        foreach ((array) config('foundation.pdf.fonts', []) as $name => $file) {
            if (is_file(base_path('resources/fonts/'.$file))) {
                $fontData[$name] = ['R' => $file, 'B' => $file, 'useOTL' => 0xFF, 'useKashida' => 75];
            }
        }

        return $fontData;
    }

    /**
     * The configured default font when it is available, else DejaVu Sans, which
     * ships with mPDF and covers most scripts.
     */
    public static function defaultFont(): string
    {
        $font = (string) config('foundation.pdf.default_font', 'dejavusans');

        return array_key_exists($font, self::getMpdfFontData()) ? $font : 'dejavusans';
    }
}
