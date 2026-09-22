<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use Override;
use Symfony\Component\Process\Process;

/**
 * foundation:install against the files of a fresh `laravel new` app (Laravel 13),
 * in a throwaway directory, never the testbench app the other tests boot from.
 */
class InstallCommandTest extends TestCase
{
    private string $project;

    private string $originalBase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->project = sys_get_temp_dir().DIRECTORY_SEPARATOR.'foundation-install-'.uniqid();
        $fixtures = __DIR__.'/../fixtures/stock-app';
        $stock = Foundation::path('stubs/install/stock');

        foreach ([
            'app/Models/User.php' => "$stock/User.php.stub",
            'database/factories/UserFactory.php' => "$stock/UserFactory.php.stub",
            'database/seeders/DatabaseSeeder.php' => "$stock/DatabaseSeeder.php.stub",
            'routes/web.php' => "$stock/web.php.stub",
            'resources/css/app.css' => "$stock/app.css.stub",
            'resources/js/app.js' => "$stock/app.js.stub",
            'vite.config.js' => "$stock/vite.config.js.stub",
            'tests/Feature/ExampleTest.php' => "$stock/ExampleTest.php.stub",
            'bootstrap/app.php' => "$fixtures/bootstrap/app.php.stub",
            'config/modules.php' => "$fixtures/config/modules.php.stub",
            'composer.json' => "$fixtures/composer.json",
            'package.json' => "$fixtures/package.json",
            '.gitignore' => "$fixtures/gitignore",
        ] as $target => $source) {
            File::ensureDirectoryExists(dirname("$this->project/$target"));
            File::copy($source, "$this->project/$target");
        }

        foreach (['0001_01_01_000000_create_users_table', '0001_01_01_000001_create_cache_table', '0001_01_01_000002_create_jobs_table'] as $migration) {
            File::ensureDirectoryExists("$this->project/database/migrations");
            File::put("$this->project/database/migrations/$migration.php", '<?php');
        }

        $this->originalBase = $this->app->basePath();
        $this->app->setBasePath($this->project);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalBase);
        File::deleteDirectory($this->project);

        parent::tearDown();
    }

    /**
     * The migration prompt is asked in tests even with --no-interaction.
     */
    private function install(): PendingCommand
    {
        return $this->artisan('foundation:install')
            ->expectsConfirmation("Laravel's stock users, cache and jobs migrations would replace the foundation's tables. Remove them?", 'yes');
    }

    private function read(string $relative): string
    {
        return str_replace("\r\n", "\n", File::get("$this->project/$relative"));
    }

    /**
     * Every file except the published theme and synced tooling, keyed by path, as content hashes.
     *
     * @return array<string, string>
     */
    private function snapshot(): array
    {
        $files = [];

        foreach (File::allFiles($this->project, true) as $file) {
            $path = str_replace('\\', '/', $file->getRelativePathname());

            if (! preg_match('#^(public/|\.ai/|\.scripts/|\.github/|pint\.json)#', $path)) {
                $files[$path] = md5_file($file->getPathname());
            }
        }

        ksort($files);

        return $files;
    }

    public function test_dry_run_lists_the_changes_and_writes_nothing(): void
    {
        $before = $this->snapshot();

        $this->artisan('foundation:install', ['--dry-run' => true])
            ->expectsOutputToContain('bootstrap/app.php')
            ->expectsOutputToContain('would be replaced')
            ->assertSuccessful();

        $this->assertSame($before, $this->snapshot());
        $this->assertDirectoryDoesNotExist("$this->project/public/assets");
    }

    public function test_it_turns_a_fresh_laravel_app_into_a_foundation_project(): void
    {
        $this->install()->assertSuccessful();

        $this->assertStringContainsString('extends FoundationUser', $this->read('app/Models/User.php'));
        $this->assertStringContainsString("'is_active' => true", $this->read('database/factories/UserFactory.php'));
        $this->assertStringContainsString('FoundationSeeder::class', $this->read('database/seeders/DatabaseSeeder.php'));
        $this->assertStringContainsString("route('admin.dashboard')", $this->read('routes/web.php'));
        $this->assertStringContainsString("assertRedirect(route('admin.dashboard'))", $this->read('tests/Feature/ExampleTest.php'));
        $this->assertStringContainsString("@import '@foundation/css/app.css'", $this->read('resources/css/app.css'));
        $this->assertStringContainsString('@foundation', $this->read('vite.config.js'));
        $this->assertStringContainsString('Foundation::modulesPath()', $this->read('config/modules.php'));
        $this->assertFileDoesNotExist("$this->project/database/migrations/0001_01_01_000000_create_users_table.php");
        $this->assertStringContainsString('/public/assets', $this->read('.gitignore'));
        $this->assertTrue(json_decode($this->read('modules_statuses.json'), true)['User']);

        $bootstrap = $this->read('bootstrap/app.php');
        $this->assertStringContainsString('use Mrj\Foundation\Foundation;', $bootstrap);
        $this->assertStringContainsString('->withMiddleware(Foundation::middleware(function (Middleware $middleware): void {', $bootstrap);
        $this->assertStringContainsString('->withExceptions(Foundation::exceptions(function (Exceptions $exceptions): void {', $bootstrap);
        $this->assertStringContainsString('->withSingletons(Foundation::singletons())', $bootstrap);
        // The app's own exception rules survive, inside the foundation's wrapper.
        $this->assertStringContainsString('shouldRenderJsonWhen', $bootstrap);
        $this->assertNotEmpty(token_get_all($bootstrap, TOKEN_PARSE));

        $composer = json_decode($this->read('composer.json'), true);
        $this->assertContains('@php artisan foundation:publish --ansi', $composer['scripts']['post-autoload-dump']);
        $this->assertContains('@php artisan foundation:sync --ansi', $composer['scripts']['post-update-cmd']);
        // Written back as objects: an empty list stays a list and nothing else is reordered.
        $this->assertStringContainsString('"dont-discover": []', $this->read('composer.json'));
        $this->assertSame(array_keys(json_decode(File::get(__DIR__.'/../fixtures/stock-app/composer.json'), true)), array_keys($composer));

        $package = json_decode($this->read('package.json'), true);
        $this->assertArrayHasKey('alpinejs', $package['devDependencies']);
        $this->assertArrayHasKey('vite', $package['devDependencies']);
    }

    /**
     * Every PHP file the installer writes passes the project's own style check, with
     * the pint.json foundation:sync gives it, so a fresh app's `pint --test` is green.
     */
    public function test_the_files_it_writes_pass_the_projects_pint_rules(): void
    {
        $this->install()->assertSuccessful();

        $files = array_map(fn (string $file): string => "$this->project/$file", [
            'app/Models/User.php', 'bootstrap/app.php', 'config/modules.php', 'database/factories/UserFactory.php',
            'database/seeders/DatabaseSeeder.php', 'routes/web.php', 'tests/Feature/ExampleTest.php',
        ]);

        $pint = new Process([PHP_BINARY, Foundation::path('vendor/laravel/pint/builds/pint'), '--test', '--config', "$this->project/pint.json", ...$files]);
        $pint->run();

        $this->assertTrue($pint->isSuccessful(), $pint->getOutput().$pint->getErrorOutput());
    }

    public function test_a_second_run_changes_nothing(): void
    {
        $this->install()->assertSuccessful();
        $after = $this->snapshot();

        // Nothing left to ask about either: the stock migrations are gone.
        $this->artisan('foundation:install')->assertSuccessful();

        $this->assertSame($after, $this->snapshot());
    }

    public function test_a_file_the_project_changed_is_left_alone_and_listed(): void
    {
        File::put("$this->project/app/Models/User.php", "<?php\n\nnamespace App\\Models;\n\nclass User extends \\Illuminate\\Foundation\\Auth\\User\n{\n    public function projectRule(): bool\n    {\n        return true;\n    }\n}\n");
        $custom = $this->read('app/Models/User.php');

        $this->install()
            ->expectsOutputToContain('app/Models/User.php: extend Mrj\Foundation\Models\User')
            ->assertSuccessful();

        $this->assertSame($custom, $this->read('app/Models/User.php'));
    }

    public function test_declining_leaves_the_stock_migrations_and_lists_them(): void
    {
        $this->artisan('foundation:install')
            ->expectsConfirmation("Laravel's stock users, cache and jobs migrations would replace the foundation's tables. Remove them?", 'no')
            ->assertSuccessful();

        $this->assertFileExists("$this->project/database/migrations/0001_01_01_000000_create_users_table.php");
    }
}
