<?php

namespace Mrj\Foundation\Tests\Feature;

use Illuminate\Support\Facades\File;
use Mrj\Foundation\Console\PublishCommand;
use Mrj\Foundation\Tests\TestCase;

class CommandsTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(public_path('assets'));
        File::deleteDirectory(base_path('.ai'));
        File::deleteDirectory(base_path('.scripts'));
        File::deleteDirectory(base_path('.github'));
        File::deleteDirectory(base_path('Modules/ProductCategory'));
        File::delete(base_path('modules_statuses.json'));
        File::delete([base_path('pint.json'), base_path('phpunit.xml')]);

        parent::tearDown();
    }

    public function test_publish_copies_the_theme_once(): void
    {
        $this->artisan('foundation:publish')->assertSuccessful();

        $this->assertFileExists(public_path('assets/'.PublishCommand::STAMP_FILE));
        $this->assertFileExists(public_path('assets/css/foundation.css'));
        $this->assertFileExists(public_path('assets/vendor/sweetalert2/sweetalert2.all.min.js'));
        $this->assertFileExists(public_path('images/person.png'));

        // A marker inside the published copy survives a second run only if that run skipped the copy.
        File::put(public_path('assets/marker.txt'), 'kept');
        $this->artisan('foundation:publish')->assertSuccessful();
        $this->assertFileExists(public_path('assets/marker.txt'));

        $this->artisan('foundation:publish', ['--force' => true])->assertSuccessful();
        $this->assertFileDoesNotExist(public_path('assets/marker.txt'));
    }

    public function test_sync_writes_shared_files_and_check_detects_drift(): void
    {
        $this->artisan('foundation:sync', ['--check' => true])->assertFailed();

        $this->artisan('foundation:sync')->assertSuccessful();
        $this->assertFileExists(base_path('.ai/guidelines/foundation/module-creation.md'));
        $this->assertFileExists(base_path('.scripts/laravel.sh'));
        $this->assertFileExists(base_path('.github/workflows/deploy-production.yml'));

        $this->artisan('foundation:sync', ['--check' => true])->assertSuccessful();

        File::put(base_path('pint.json'), '{}');
        $this->artisan('foundation:sync', ['--check' => true])->assertFailed();

        // phpunit.xml is created once and then owned by the project.
        File::put(base_path('phpunit.xml'), '<phpunit/>');
        $this->artisan('foundation:sync')->assertSuccessful();
        $this->assertSame('<phpunit/>', File::get(base_path('phpunit.xml')));
        $this->artisan('foundation:sync', ['--check' => true])->assertSuccessful();
    }

    public function test_make_module_generates_a_module_on_the_foundation_conventions(): void
    {
        config(['modules.activators.file.statuses-file' => base_path('modules_statuses.json')]);

        $this->artisan('foundation:make-module', ['name' => 'product categories', '--group' => 'settings'])->assertSuccessful();

        $module = base_path('Modules/ProductCategory');

        foreach ([
            'module.json', 'composer.json', 'routes/web.php', 'config/permissions.php', 'resources/views/index.blade.php',
            'app/Providers/ProductCategoryServiceProvider.php', 'app/Models/ProductCategory.php', 'tests/Feature/ProductCategoryTest.php',
            'lang/en/productcategory.php',
        ] as $file) {
            $this->assertFileExists($module.'/'.$file);
        }

        $lang = require $module.'/lang/en/productcategory.php';

        foreach (File::allFiles($module) as $file) {
            $contents = $file->getContents();
            $this->assertDoesNotMatchRegularExpression('/__[A-Z_]+__/', str_replace('__DIR__', '', $contents), $file->getRelativePathname());

            if ($file->getExtension() === 'php' && ! str_ends_with($file->getFilename(), '.blade.php')) {
                $this->assertNotEmpty(token_get_all($contents, TOKEN_PARSE));
            }

            // A stub importing a package class that was since removed only fails once the page is requested.
            preg_match_all('/^use (Mrj\\\\Foundation\\\\[\w\\\\]+);/m', $contents, $imports);
            foreach ($imports[1] as $class) {
                $this->assertTrue(class_exists($class) || interface_exists($class) || trait_exists($class), "{$file->getRelativePathname()} imports missing $class");
            }

            preg_match_all("/__\('(productcategory|foundation)::\\1\.([\w.]+)'\)/", $contents, $keys, PREG_SET_ORDER);
            foreach ($keys as [, $namespace, $key]) {
                $resolved = $namespace === 'foundation' ? __("foundation::foundation.$key") : data_get($lang, $key);
                $this->assertIsString($resolved, "{$file->getRelativePathname()} uses unknown key $namespace::$key");
                $this->assertNotSame("foundation::foundation.$key", $resolved);
            }
        }

        $this->assertCount(1, File::glob($module.'/database/migrations/*_create_product_categories_table.php'));
        $this->assertStringContainsString("'product_category' => ProductCategory::class", File::get($module.'/app/Providers/ProductCategoryServiceProvider.php'));
        $this->assertStringContainsString("'route' => 'admin.product-categories.index'", File::get($module.'/config/menu.php'));
        $this->assertStringContainsString("'group' => 'settings'", File::get($module.'/config/menu.php'));
        $this->assertTrue(json_decode(File::get(base_path('modules_statuses.json')), true)['ProductCategory']);
        $this->assertSame('universal', json_decode(File::get($module.'/module.json'), true)['context']);

        $this->artisan('foundation:make-module', ['name' => 'ProductCategory'])->assertFailed();
        $this->artisan('foundation:make-module', ['name' => 'User'])->assertFailed();
    }

    public function test_make_module_records_the_tenancy_context(): void
    {
        config(['modules.activators.file.statuses-file' => base_path('modules_statuses.json')]);

        $this->artisan('foundation:make-module', ['name' => 'ProductCategory', '--context' => 'somewhere'])->assertFailed();
        $this->assertDirectoryDoesNotExist(base_path('Modules/ProductCategory'));

        $this->artisan('foundation:make-module', ['name' => 'ProductCategory', '--context' => 'tenant'])->assertSuccessful();

        $manifest = json_decode(File::get(base_path('Modules/ProductCategory/module.json')), true);
        $this->assertSame('tenant', $manifest['context']);
        $this->assertSame('Modules\\ProductCategory\\Providers\\ProductCategoryServiceProvider', $manifest['providers'][0]);
    }
}
