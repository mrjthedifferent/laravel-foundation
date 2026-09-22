<?php

namespace Mrj\Foundation\Services;

use Mrj\Foundation\Contracts\FileStorage;

/**
 * A static facade over whatever FileStorage is bound in the container —
 * the same relationship Laravel's own Storage facade has to the filesystem
 * manager. Kept for the many call sites (blade views, model accessors) where
 * a static call is more convenient than constructor injection; anything that
 * wants a swappable, mockable dependency should type-hint FileStorage directly.
 */
class FileManagerService
{
    public static function uploadFile(mixed $file, ?string $existing_file = null, string $directory = 'files', ?string $disk = null, bool $isBase64 = false): ?string
    {
        return app(FileStorage::class)->uploadFile($file, $existing_file, $directory, $disk, $isBase64);
    }

    public static function getImage(?string $filePath, ?string $disk = null, ?string $default = 'images/default.png'): ?string
    {
        return app(FileStorage::class)->getImage($filePath, $disk, $default);
    }

    public static function getFile(?string $filePath, ?string $disk = null, bool $getPath = false): ?string
    {
        return app(FileStorage::class)->getFile($filePath, $disk, $getPath);
    }

    public static function deleteFile(?string $filePath, ?string $disk = null): bool
    {
        return app(FileStorage::class)->deleteFile($filePath, $disk);
    }

    /**
     * @return list<string>
     */
    public static function listFiles(string $directory, ?string $disk = null): array
    {
        return app(FileStorage::class)->listFiles($directory, $disk);
    }

    public static function fileExists(string $filePath, ?string $disk = null): bool
    {
        return app(FileStorage::class)->fileExists($filePath, $disk);
    }
}
