<?php

namespace Mrj\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Mrj\Foundation\Foundation;

/** @internal */
final class SyncCommand extends Command
{
    protected $signature = 'foundation:sync
        {--check : Report drift without writing; exits non-zero when a managed file differs}';

    protected $description = 'Sync shared tooling files (AI guidelines, pint.json, deploy script) from the foundation';

    public function handle(Filesystem $files): int
    {
        $check = (bool) $this->option('check');
        $drifted = 0;

        foreach ($this->entries($files) as [$source, $target, $mode]) {
            $exists = $files->exists($target);

            if ($mode === 'create' && $exists) {
                continue;
            }

            if ($exists && $files->hash($source) === $files->hash($target)) {
                continue;
            }

            $drifted++;
            $relative = str_replace('\\', '/', ltrim(str_replace(base_path(), '', $target), '/\\'));

            if ($check) {
                $this->components->twoColumnDetail($relative, $exists ? 'DIFFERS' : 'MISSING');

                continue;
            }

            $files->ensureDirectoryExists(dirname($target));
            $files->copy($source, $target);
            $this->components->twoColumnDetail($relative, $exists ? 'updated' : 'created');
        }

        if ($drifted === 0) {
            $this->components->info('Shared files are in sync.');

            return self::SUCCESS;
        }

        if ($check) {
            $this->components->error("$drifted shared file(s) out of sync. Run: php artisan foundation:sync");

            return self::FAILURE;
        }

        $this->components->info("Synced $drifted file(s).");

        return self::SUCCESS;
    }

    /**
     * Manifest entries flattened to single files.
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function entries(Filesystem $files): array
    {
        $entries = [];

        $except = array_map(fn ($path) => trim(str_replace('\\', '/', $path), '/'), (array) config('foundation.sync.except', []));

        foreach (require Foundation::path('sync/manifest.php') as $entry) {
            // A project keeps its own version of anything it lists in foundation.sync.except.
            if (in_array(trim($entry['to'], '/'), $except, true)) {
                continue;
            }

            $source = Foundation::path('sync/'.$entry['from']);
            $target = base_path($entry['to']);

            if (! $files->isDirectory($source)) {
                $entries[] = [$source, $target, $entry['mode']];

                continue;
            }

            foreach ($files->allFiles($source) as $file) {
                $entries[] = [
                    $file->getPathname(),
                    $target.DIRECTORY_SEPARATOR.$file->getRelativePathname(),
                    $entry['mode'],
                ];
            }
        }

        return $entries;
    }
}
