<?php

namespace Mrj\Foundation\Services;

use Illuminate\Support\Facades\Storage;

class FileManagerService
{
    private static function isUrl($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    private static function getStoragePath($url, $disk = 'public'): string
    {
        return str_replace(Storage::disk($disk)->url(''), '', $url);
    }

    /**
     * Upload an image to local storage and return the file path.
     */
    public static function uploadFile($file, ?string $existing_file = null, string $directory = 'files', string $disk = 'public', bool $isBase64 = false): ?string
    {
        if (! $file) {
            return null;
        }

        if (self::isUrl($file)) {
            return self::getStoragePath($file, $disk);
        }

        if (is_string($file) && ! $isBase64) {
            return $file;
        }

        if ($isBase64) {
            $filename = md5(uniqid()).'.png';
            $path = $directory.'/'.$filename;

            $file = base64_decode(
                preg_replace('#^data:image/\w+;base64,#i', '', $file)
            );

            Storage::disk($disk)->put($path, $file);
        } else {
            $path = Storage::disk($disk)->putFile($directory, $file);
        }

        if ($existing_file) {
            self::deleteFile($existing_file, $disk);
        }

        return $path;
    }

    /**
     * Get the file path from local storage.
     */
    public static function getImage(?string $filePath, string $disk = 'public', ?string $default = 'images/default.png'): ?string
    {
        if (! $default && ! $filePath) {
            return null;
        }
        if (self::isUrl($filePath)) {
            return $filePath;
        }

        return $filePath ? Storage::disk($disk)->url($filePath) : url($default);
    }

    /**
     * Get the file path from local storage.
     */
    public static function getFile(?string $filePath, string $disk = 'public', bool $getPath = false): ?string
    {
        if ($getPath) {
            return $filePath ? Storage::disk($disk)->path($filePath) : null;
        }

        return $filePath ? Storage::disk($disk)->url($filePath) : null;
    }

    /**
     * Delete an image from local storage.
     */
    public static function deleteFile(?string $filePath, string $disk = 'public'): bool
    {
        if (! $filePath) {
            return true;
        }

        $relative = self::toDiskRelativePath($filePath, $disk);
        if ($relative === null || $relative === '') {
            return true;
        }

        return Storage::disk($disk)->delete($relative);
    }

    /**
     * Resolve a stored value (relative path or full URL) to a path relative to the disk root.
     */
    private static function toDiskRelativePath(string $filePath, string $disk): ?string
    {
        if (self::isUrl($filePath)) {
            $prefix = Storage::disk($disk)->url('');
            $stripped = str_replace($prefix, '', $filePath);
            if ($stripped !== $filePath && $stripped !== '') {
                return ltrim($stripped, '/');
            }

            if (preg_match('#/storage/([^?]+)#', $filePath, $matches)) {
                return $matches[1];
            }

            return null;
        }

        return $filePath;
    }

    /**
     * List all files in a directory for a given disk.
     */
    public static function listFiles(string $directory, string $disk = 'public'): array
    {
        return \Storage::disk($disk)->files($directory);
    }

    /**
     * Check if a file exists in a given disk.
     */
    public static function fileExists(string $filePath, string $disk = 'public'): bool
    {
        return \Storage::disk($disk)->exists($filePath);
    }
}
