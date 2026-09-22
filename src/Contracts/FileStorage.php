<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

/**
 * Always bound to LocalFileStorage by default (see FoundationServiceProvider);
 * a project may rebind it to point uploads somewhere else entirely.
 * FileManagerService is the static facade most call sites use — it delegates
 * to whatever is bound here, the same relationship Laravel's own Storage
 * facade has to the filesystem manager.
 */
interface FileStorage
{
    public function uploadFile(mixed $file, ?string $existingFile = null, string $directory = 'files', ?string $disk = null, bool $isBase64 = false): ?string;

    public function getImage(?string $filePath, ?string $disk = null, ?string $default = 'images/default.png'): ?string;

    public function getFile(?string $filePath, ?string $disk = null, bool $getPath = false): ?string;

    public function deleteFile(?string $filePath, ?string $disk = null): bool;

    /**
     * @return list<string>
     */
    public function listFiles(string $directory, ?string $disk = null): array;

    public function fileExists(string $filePath, ?string $disk = null): bool;
}
