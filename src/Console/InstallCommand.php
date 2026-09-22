<?php

namespace Mrj\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Mrj\Foundation\Foundation;
use stdClass;

/**
 * Turns a fresh Laravel app into a working admin panel. Every step checks the
 * project first, so running it again changes nothing. A file the project has
 * already edited is never overwritten: the step is listed as a manual one.
 *
 * @internal
 */
final class InstallCommand extends Command
{
    protected $signature = 'foundation:install {--dry-run : List what would change without writing anything}';

    protected $description = 'Set up a project to use the foundation (safe to run again)';

    /**
     * Always on. Otp and ErrorReport are optional: php artisan module:enable Otp
     */
    private const array CORE_MODULES = ['RolePermission', 'Settings', 'Notification', 'ActivityLog', 'ImportDownloadManager', 'BackupCleanup', 'User'];

    private const string SCAN_DEFAULT = "'scan' => [\n        'enabled' => false,\n        'paths' => [\n            base_path('vendor/*/*'),\n        ],\n    ],";

    private const string SCAN_FOUNDATION = "'scan' => [\n        'enabled' => true,\n        'paths' => [\n            \\Mrj\\Foundation\\Foundation::modulesPath(),\n        ],\n    ],";

    private const array STOCK_MIGRATIONS = ['0001_01_01_000000_create_users_table', '0001_01_01_000001_create_cache_table', '0001_01_01_000002_create_jobs_table'];

    /** @var array<string, string> the foundation's front-end needs these on top of Vite */
    private const array NPM_PACKAGES = ['alpinejs' => '^3.4.2', 'axios' => '^1.7.4', 'laravel-echo' => '^2.3.1', 'pusher-js' => '^8.4.0'];

    private Filesystem $files;

    /** @var list<string> */
    private array $manual = [];

    public function handle(Filesystem $files): int
    {
        $this->files = $files;

        if ($this->dryRun()) {
            $this->components->info('Dry run: nothing is written.');
        } else {
            $this->call('foundation:publish');
            $this->call('foundation:sync');
        }

        $this->removeStockMigrations();
        $this->ignorePublishedAssets();
        $this->ensureModuleStatuses();
        $this->configureModuleScan();
        $this->patchBootstrap();

        $this->replaceStock('app/Models/User.php', 'User.php', fn (string $c): bool => str_contains($c, 'Mrj\Foundation\Models\User'),
            'extend Mrj\Foundation\Models\User (use Mrj\Foundation\Models\User as FoundationUser; class User extends FoundationUser)');
        $this->replaceStock('database/factories/UserFactory.php', 'UserFactory.php', fn (string $c): bool => str_contains($c, "'is_active'"),
            "add 'is_active' => true to definition(), or factory users cannot sign in");
        $this->replaceStock('database/seeders/DatabaseSeeder.php', 'DatabaseSeeder.php', fn (string $c): bool => str_contains($c, 'FoundationSeeder'),
            'call $this->call(\Mrj\Foundation\Database\Seeders\FoundationSeeder::class) first in run()');
        $this->replaceStock('routes/web.php', 'web.php', fn (string $c): bool => str_contains($c, 'admin.dashboard'));
        // The stock test expects GET / to answer 200; it now redirects to the dashboard.
        $this->replaceStock('tests/Feature/ExampleTest.php', 'ExampleTest.php', fn (string $c): bool => str_contains($c, 'admin.dashboard'));

        $this->patchComposerScripts();

        $this->replaceStock('resources/css/app.css', 'app.css', fn (string $c): bool => str_contains($c, '@foundation'),
            "import the foundation's styles: @import '@foundation/css/app.css';");
        $this->replaceStock('resources/js/app.js', 'app.js', fn (string $c): bool => str_contains($c, '@foundation'),
            "import the foundation's scripts: import '@foundation/js/app.js';");
        $this->replaceStock('vite.config.js', 'vite.config.js', fn (string $c): bool => str_contains($c, '@foundation'),
            "alias '@foundation' to vendor/mrjthedifferent/laravel-foundation/ui/resources");
        $this->addNpmPackages();

        $this->report();

        return self::SUCCESS;
    }

    private function dryRun(): bool
    {
        return (bool) $this->option('dry-run');
    }

    private function write(string $relative, string $contents, string $what): void
    {
        if ($this->dryRun()) {
            $this->components->twoColumnDetail($relative, "would be $what");

            return;
        }

        $this->files->ensureDirectoryExists(dirname(base_path($relative)));
        $this->files->put(base_path($relative), $contents);
        $this->components->twoColumnDetail($relative, $what);
    }

    /**
     * Replace a file that is still exactly as Laravel ships it. One the project
     * has changed is left alone and, when it matters, listed as a manual step.
     *
     * @param  callable(string): bool  $isDone
     */
    private function replaceStock(string $relative, string $stub, callable $isDone, ?string $manual = null): void
    {
        $path = base_path($relative);
        $current = $this->files->exists($path) ? $this->files->get($path) : null;

        if ($current !== null && $isDone($current)) {
            return;
        }

        $stock = $this->files->get(Foundation::path("stubs/install/stock/$stub.stub"));

        if ($current === null || $this->normalize($current) === $this->normalize($stock)) {
            $this->write($relative, $this->files->get(Foundation::path("stubs/install/$stub.stub")), $current === null ? 'created' : 'replaced');

            return;
        }

        if ($manual !== null) {
            $this->manual[] = "$relative: $manual";
        }
    }

    private function normalize(string $contents): string
    {
        return trim(preg_replace('/[ \t]+$/m', '', str_replace("\r\n", "\n", $contents)) ?? $contents);
    }

    /**
     * A fresh Laravel app ships users, cache and jobs migrations under the same
     * filenames the foundation uses, and the project's copy would win. The
     * foundation owns those tables, so the stock files go, after asking.
     */
    private function removeStockMigrations(): void
    {
        $found = array_values(array_filter(
            array_map(fn (string $name): string => "database/migrations/$name.php", self::STOCK_MIGRATIONS),
            fn (string $relative): bool => $this->files->exists(base_path($relative)),
        ));

        if ($found === []) {
            return;
        }

        if ($this->dryRun()) {
            foreach ($found as $relative) {
                $this->components->twoColumnDetail($relative, 'would be removed (provided by the foundation)');
            }

            return;
        }

        if (! $this->components->confirm("Laravel's stock users, cache and jobs migrations would replace the foundation's tables. Remove them?", true)) {
            $this->manual[] = 'remove '.implode(', ', $found).': the foundation creates these tables with its own columns';

            return;
        }

        foreach ($found as $relative) {
            $this->files->delete(base_path($relative));
            $this->components->twoColumnDetail($relative, 'removed (provided by the foundation)');
        }
    }

    private function ignorePublishedAssets(): void
    {
        $path = base_path('.gitignore');
        $contents = $this->files->exists($path) ? $this->files->get($path) : '';

        if (in_array('/public/assets', array_map('trim', preg_split('/\R/', $contents) ?: []), true)) {
            return;
        }

        $this->write('.gitignore', rtrim($contents)."\n/public/assets\n", 'given /public/assets');
    }

    private function ensureModuleStatuses(): void
    {
        if ($this->files->exists(base_path('modules_statuses.json'))) {
            return;
        }

        $this->write('modules_statuses.json', json_encode(array_fill_keys(self::CORE_MODULES, true), JSON_PRETTY_PRINT)."\n", 'created, core modules enabled');
    }

    /**
     * Point nwidart's scanner at the foundation's modules directory. The scanner
     * reads this while the modules package registers, before this package's
     * provider runs, so it has to live in the project's config file.
     */
    private function configureModuleScan(): void
    {
        $path = config_path('modules.php');

        if (! $this->files->exists($path)) {
            if ($this->dryRun()) {
                $this->components->twoColumnDetail('config/modules.php', 'would be published with the scan path set');

                return;
            }

            $this->callSilent('vendor:publish', ['--provider' => 'Nwidart\\Modules\\LaravelModulesServiceProvider', '--tag' => 'config']);
        }

        $config = $this->files->exists($path) ? str_replace("\r\n", "\n", $this->files->get($path)) : '';

        if (str_contains($config, 'Foundation::modulesPath()')) {
            return;
        }

        if (! str_contains($config, self::SCAN_DEFAULT)) {
            $this->manual[] = "config/modules.php: set 'scan' to enabled => true, paths => [\\Mrj\\Foundation\\Foundation::modulesPath()]";

            return;
        }

        $this->write('config/modules.php', str_replace(self::SCAN_DEFAULT, self::SCAN_FOUNDATION, $config), 'given the scan path');
    }

    /**
     * Wrap the stock withMiddleware/withExceptions closures in the foundation's
     * and add its exception handler. The closures' own bodies are kept.
     */
    private function patchBootstrap(): void
    {
        $relative = 'bootstrap/app.php';
        $contents = str_replace("\r\n", "\n", $this->files->get(base_path($relative)));

        if (str_contains($contents, 'Foundation::middleware')) {
            return;
        }

        $patched = preg_replace(
            '/->withMiddleware\(function \(Middleware \$middleware\)(?:: void)? \{(.*?)\n    \}\)/s',
            "->withMiddleware(Foundation::middleware(function (Middleware \$middleware): void {\$1\n    }))",
            $contents, 1, $middleware,
        );
        $patched = preg_replace(
            '/->withExceptions\(function \(Exceptions \$exceptions\)(?:: void)? \{(.*?)\n    \}\)/s',
            "->withExceptions(Foundation::exceptions(function (Exceptions \$exceptions): void {\$1\n    }))",
            (string) $patched, 1, $exceptions,
        );

        if ($middleware !== 1 || $exceptions !== 1 || str_contains((string) $patched, 'withSingletons')) {
            $this->manual[] = "$relative: ->withMiddleware(Foundation::middleware(...)), ->withExceptions(Foundation::exceptions(...)) and ->withSingletons(Foundation::singletons()), with use Mrj\\Foundation\\Foundation;";

            return;
        }

        $patched = preg_replace('/\)\s*->create\(\);/', ")\n    ->withSingletons(Foundation::singletons())\n    ->create();", (string) $patched, 1);
        $patched = preg_replace('/((?:^use [^;]+;\n)+)/m', "\$1use Mrj\\Foundation\\Foundation;\n", (string) $patched, 1);

        $this->write($relative, (string) $patched, 'wired to the foundation');
    }

    /**
     * Keep the theme assets and shared tooling files current on every Composer run.
     */
    private function patchComposerScripts(): void
    {
        $this->patchJson('composer.json', function (stdClass $json): bool {
            $json->scripts ??= new stdClass;
            $changed = false;

            foreach (['post-autoload-dump' => '@php artisan foundation:publish --ansi', 'post-update-cmd' => '@php artisan foundation:sync --ansi'] as $event => $command) {
                $scripts = (array) ($json->scripts->{$event} ?? []);

                if (! in_array($command, $scripts, true)) {
                    $json->scripts->{$event} = [...$scripts, $command];
                    $changed = true;
                }
            }

            return $changed;
        }, 'given the foundation:publish and foundation:sync scripts');
    }

    private function addNpmPackages(): void
    {
        $this->patchJson('package.json', function (stdClass $json): bool {
            $json->devDependencies ??= new stdClass;
            $changed = false;

            foreach (self::NPM_PACKAGES as $package => $version) {
                if (! isset($json->devDependencies->{$package}) && ! isset($json->dependencies->{$package})) {
                    $json->devDependencies->{$package} = $version;
                    $changed = true;
                }
            }

            return $changed;
        }, 'given '.implode(', ', array_keys(self::NPM_PACKAGES)));
    }

    /**
     * Decoded as objects, not arrays, so an empty {} stays {} when written back.
     *
     * @param  callable(stdClass): bool  $change
     */
    private function patchJson(string $relative, callable $change, string $what): void
    {
        $path = base_path($relative);

        if (! $this->files->exists($path)) {
            return;
        }

        $json = json_decode($this->files->get($path));

        if (! $json instanceof stdClass) {
            $this->manual[] = "$relative: could not be read as JSON; $what by hand";

            return;
        }

        if ($change($json)) {
            $this->write($relative, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n", $what);
        }
    }

    private function report(): void
    {
        if ($this->manual !== []) {
            $this->components->warn('These files were changed by the project, so they were left alone. Do these by hand:');
            $this->components->bulletList($this->manual);
        }

        $this->components->info('Foundation '.Foundation::version().($this->dryRun() ? ': dry run finished.' : ' installed.'));
        $this->components->bulletList([
            'php artisan migrate --seed   (set SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD in .env first outside local)',
            'npm install && npm run build',
        ]);
    }
}
