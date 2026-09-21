<?php

namespace Mrj\Foundation\Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mrj\Foundation\Services\FileManagerService;
use Mrj\Foundation\Tests\TestCase;

class FileManagerServiceDiskTest extends TestCase
{
    public function test_it_uses_the_public_disk_by_default(): void
    {
        Storage::fake('public');

        $path = FileManagerService::uploadFile(
            UploadedFile::fake()->image('avatar.jpg'),
            directory: 'avatars'
        );

        Storage::disk('public')->assertExists($path);
    }

    public function test_it_uses_the_configured_disk_when_no_disk_is_given_explicitly(): void
    {
        config(['foundation.storage.disk' => 'local']);
        Storage::fake('local');

        $path = FileManagerService::uploadFile(
            UploadedFile::fake()->image('avatar.jpg'),
            directory: 'avatars'
        );

        Storage::disk('local')->assertExists($path);
    }
}
