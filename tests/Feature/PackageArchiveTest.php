<?php

namespace Mrj\Foundation\Tests\Feature;

use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use Symfony\Component\Process\Process;

/**
 * Projects install the package from Packagist, which serves `git archive` of a tag, so anything
 * marked `export-ignore` in .gitattributes is missing from `vendor/`. Every file the docs tell a
 * project to run from `vendor/mrjthedifferent/laravel-foundation/` must therefore be shipped.
 */
class PackageArchiveTest extends TestCase
{
    /**
     * @return list<string> package-relative paths, e.g. "bin/migrate-bootstrap-to-tailwind.mjs"
     */
    private function pathsDocumentedInVendor(): array
    {
        $docs = array_merge([Foundation::path('CHANGELOG.md')], glob(Foundation::path('sync/guidelines/*.md')) ?: []);
        $paths = [];

        foreach ($docs as $doc) {
            preg_match_all('#vendor/mrjthedifferent/laravel-foundation/([\w./-]+\.\w+)#', (string) file_get_contents($doc), $matches);
            array_push($paths, ...$matches[1]);
        }

        return array_values(array_unique($paths));
    }

    /**
     * `git archive` drops a file when it, or any folder above it, is `export-ignore`
     * (`git check-attr` on the file alone does not apply a folder's rule).
     */
    private function shipped(string $path): bool
    {
        $candidates = [$path];
        for ($dir = dirname($path); $dir !== '.' && $dir !== ''; $dir = dirname($dir)) {
            $candidates[] = $dir;
        }

        $check = new Process(['git', 'check-attr', 'export-ignore', '--', ...$candidates], Foundation::path());
        $check->mustRun();

        return ! str_contains($check->getOutput(), 'export-ignore: set');
    }

    public function test_files_the_docs_run_from_vendor_are_in_the_archive(): void
    {
        $paths = $this->pathsDocumentedInVendor();
        $this->assertContains('bin/migrate-bootstrap-to-tailwind.mjs', $paths, 'the upgrade guide names the converter');

        foreach ($paths as $path) {
            $this->assertFileExists(Foundation::path($path));
            $this->assertTrue(
                $this->shipped($path),
                "{$path} is documented as vendor/mrjthedifferent/laravel-foundation/{$path} but .gitattributes leaves it out of the package.",
            );
        }
    }

    public function test_build_tooling_stays_out_of_the_archive(): void
    {
        foreach (['bin/build-css.sh', 'bin/build-js.sh', 'bin/build-assets.sh', 'bin/test-js.sh', 'tests/TestCase.php'] as $path) {
            $this->assertFalse($this->shipped($path), "{$path} should not ship to projects.");
        }
    }
}
