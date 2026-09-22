<?php

namespace Mrj\Foundation\Services;

use Illuminate\Support\Facades\Storage;
use Mrj\Foundation\Contracts\FileStorage;
use Override;

/**
 * The default FileStorage: uploads go to whichever disk is given, falling
 * back to foundation.storage.disk. Moved here, instance-based, out of
 * FileManagerService's static methods so it is swappable and mockable
 * through the FileStorage contract; FileManagerService itself remains as a
 * static facade over whatever implementation is bound.
 *
 * @internal
 */
final class LocalFileStorage implements FileStorage
{
    private function isUrl(mixed $url): bool
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    private function getStoragePath(string $url, ?string $disk = null): string
    {
        $disk ??= config('foundation.storage.disk', 'public');

        return str_replace(Storage::disk($disk)->url(''), '', $url);
    }

    #[Override]
    public function uploadFile(mixed $file, ?string $existingFile = null, string $directory = 'files', ?string $disk = null, bool $isBase64 = false): ?string
    {
        $disk ??= config('foundation.storage.disk', 'public');

        if (! $file) {
            return null;
        }

        if ($this->isUrl($file)) {
            return $this->getStoragePath($file, $disk);
        }

        if (is_string($file) && ! $isBase64) {
            return $file;
        }

        if ($isBase64) {
            $filename = md5(uniqid()).'.png';
            $path = $directory.'/'.$filename;

            $decoded = base64_decode(
                preg_replace('#^data:image/\w+;base64,#i', '', $file)
            );

            Storage::disk($disk)->put($path, $decoded);
        } else {
            $path = Storage::disk($disk)->putFile($directory, $file);
        }

        if ($existingFile) {
            $this->deleteFile($existingFile, $disk);
        }

        return $path;
    }

    #[Override]
    public function getImage(?string $filePath, ?string $disk = null, ?string $default = 'images/default.png'): ?string
    {
        $disk ??= config('foundation.storage.disk', 'public');

        if (! $default && ! $filePath) {
            return null;
        }

        if ($this->isUrl($filePath)) {
            return $filePath;
        }

        return $filePath ? Storage::disk($disk)->url($filePath) : url($default);
    }

    #[Override]
    public function getFile(?string $filePath, ?string $disk = null, bool $getPath = false): ?string
    {
        $disk ??= config('foundation.storage.disk', 'public');

        if ($getPath) {
            return $filePath ? Storage::disk($disk)->path($filePath) : null;
        }

        return $filePath ? Storage::disk($disk)->url($filePath) : null;
    }

    #[Override]
    public function deleteFile(?string $filePath, ?string $disk = null): bool
    {
        $disk ??= config('foundation.storage.disk', 'public');

        if (! $filePath) {
            return true;
        }

        $relative = $this->toDiskRelativePath($filePath, $disk);
        if ($relative === null || $relative === '') {
            return true;
        }

        return Storage::disk($disk)->delete($relative);
    }

    /**
     * Resolve a stored value (relative path or full URL) to a path relative to the disk root.
     */
    private function toDiskRelativePath(string $filePath, string $disk): ?string
    {
        if ($this->isUrl($filePath)) {
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

    #[Override]
    public function listFiles(string $directory, ?string $disk = null): array
    {
        return Storage::disk($disk ?? config('foundation.storage.disk', 'public'))->files($directory);
    }

    #[Override]
    public function fileExists(string $filePath, ?string $disk = null): bool
    {
        return Storage::disk($disk ?? config('foundation.storage.disk', 'public'))->exists($filePath);
    }
}
