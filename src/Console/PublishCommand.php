<?php

namespace Mrj\Foundation\Console;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Mrj\Foundation\Foundation;

class PublishCommand extends Command
{
    protected $signature = 'foundation:publish
        {--force : Copy even when the published assets are already current}
        {--link : Link public/assets to the package instead of copying (local development)}';

    protected $description = 'Publish the foundation theme assets to public/assets';

    public const STAMP_FILE = '.foundation-version';

    public function handle(Filesystem $files): int
    {
        $source = Foundation::uiPath('public/assets');
        $target = public_path('assets');

        if (! $files->isDirectory($source)) {
            $this->components->error("Theme assets not found at [$source].");

            return self::FAILURE;
        }

        if ($this->option('link')) {
            return $this->link($files, $source, $target);
        }

        $stamp = $this->stamp();
        $stampFile = $target.DIRECTORY_SEPARATOR.self::STAMP_FILE;

        if (! $this->option('force') && $files->exists($stampFile) && trim($files->get($stampFile)) === $stamp) {
            $this->components->info("Theme assets are current ($stamp).");

            return self::SUCCESS;
        }

        if (is_link($target)) {
            $files->delete($target);
        }

        // Replace rather than merge, so files removed from the theme disappear here too.
        $files->deleteDirectory($target);
        $files->copyDirectory($source, $target);
        $files->put($stampFile, $stamp.PHP_EOL);

        // Default images live beside the project's own uploads, so these are merged in, never replaced.
        $files->copyDirectory(Foundation::uiPath('public/images'), public_path('images'));

        $this->components->info("Published theme assets ($stamp).");

        return self::SUCCESS;
    }

    private function link(Filesystem $files, string $source, string $target): int
    {
        if (is_link($target)) {
            $files->delete($target);
        } else {
            $files->deleteDirectory($target);
        }

        $files->link($source, $target);
        $this->components->info("Linked [$target] to the package assets.");

        return self::SUCCESS;
    }

    /**
     * Version plus source reference: a dev or path install keeps the same version
     * string across changes, so the reference is what detects new assets there.
     */
    private function stamp(): string
    {
        $reference = InstalledVersions::isInstalled(Foundation::PACKAGE)
            ? InstalledVersions::getReference(Foundation::PACKAGE)
            : null;

        return Foundation::version().($reference ? '@'.substr($reference, 0, 12) : '');
    }
}
