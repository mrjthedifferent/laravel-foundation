<?php

namespace Mrj\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Mrj\Foundation\Foundation;

class InstallCommand extends Command
{
    protected $signature = 'foundation:install';

    protected $description = 'Set up a project to use the foundation (run once per project)';

    /**
     * Always on. Otp and ErrorReport are optional: php artisan module:enable Otp
     */
    private const CORE_MODULES = ['RolePermission', 'Settings', 'Notification', 'ActivityLog', 'ImportDownloadManager', 'BackupCleanup', 'User'];

    private const SCAN_DEFAULT = "'scan' => [\n        'enabled' => false,\n        'paths' => [\n            base_path('vendor/*/*'),\n        ],\n    ],";

    private const SCAN_FOUNDATION = "'scan' => [\n        'enabled' => true,\n        'paths' => [\n            \\Mrj\\Foundation\\Foundation::modulesPath(),\n        ],\n    ],";

    public function handle(Filesystem $files): int
    {
        $this->call('foundation:publish');
        $this->call('foundation:sync');

        $this->removeStockMigrations($files);
        $this->ignorePublishedAssets($files);
        $this->ensureModuleStatuses($files);
        $this->configureModuleScan($files);

        $this->components->info('Foundation installed ('.Foundation::version().').');

        return self::SUCCESS;
    }

    /**
     * A fresh Laravel app ships users, cache and jobs migrations under the same
     * filenames the foundation uses, and the project's copy would win. The
     * foundation owns those tables, so the stock files are removed.
     */
    private function removeStockMigrations(Filesystem $files): void
    {
        foreach (['0001_01_01_000000_create_users_table', '0001_01_01_000001_create_cache_table', '0001_01_01_000002_create_jobs_table'] as $name) {
            $path = database_path("migrations/$name.php");

            if ($files->exists($path)) {
                $files->delete($path);
                $this->components->twoColumnDetail("database/migrations/$name.php", 'removed (provided by the foundation)');
            }
        }
    }

    private function ignorePublishedAssets(Filesystem $files): void
    {
        $path = base_path('.gitignore');
        $lines = $files->exists($path) ? preg_split('/\R/', $files->get($path)) : [];

        if (in_array('/public/assets', array_map('trim', $lines), true)) {
            return;
        }

        $files->append($path, PHP_EOL.'/public/assets'.PHP_EOL);
        $this->components->twoColumnDetail('.gitignore', 'added /public/assets');
    }

    private function ensureModuleStatuses(Filesystem $files): void
    {
        $path = base_path('modules_statuses.json');

        if ($files->exists($path)) {
            return;
        }

        $files->put($path, json_encode(array_fill_keys(self::CORE_MODULES, true), JSON_PRETTY_PRINT).PHP_EOL);
        $this->components->twoColumnDetail('modules_statuses.json', 'created, core modules enabled');
    }

    /**
     * Point nwidart's scanner at the foundation's modules directory. The scanner
     * reads this while the modules package registers, before this package's
     * provider runs, so it has to live in the project's config file.
     */
    private function configureModuleScan(Filesystem $files): void
    {
        $path = config_path('modules.php');

        if (! $files->exists($path)) {
            $this->callSilent('vendor:publish', ['--provider' => 'Nwidart\\Modules\\LaravelModulesServiceProvider', '--tag' => 'config']);
        }

        $config = $files->exists($path) ? str_replace("\r\n", "\n", $files->get($path)) : '';

        if (str_contains($config, 'Foundation::modulesPath()')) {
            return;
        }

        if (! str_contains($config, self::SCAN_DEFAULT)) {
            $this->components->warn("Set 'scan' in config/modules.php to enabled => true, paths => [\\Mrj\\Foundation\\Foundation::modulesPath()].");

            return;
        }

        $files->put($path, str_replace(self::SCAN_DEFAULT, self::SCAN_FOUNDATION, $config));
        $this->components->twoColumnDetail('config/modules.php', 'scan path set');
    }
}
