<?php

namespace Mrj\Foundation\Tests\Feature;

use FilesystemIterator;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Asset file names stay the same across releases (Phosphor 1 and 2 both ship fonts/Phosphor.woff2),
 * so without a version in the URL a browser keeps its cached copy after an update and mixes old
 * and new files: blank icons, stale scripts.
 */
class AssetVersioningTest extends TestCase
{
    public function test_foundation_asset_appends_the_package_version(): void
    {
        $url = foundation_asset('assets/css/foundation.css');

        $this->assertStringStartsWith(asset('assets/css/foundation.css').'?v=', $url);
        $this->assertStringEndsWith(rawurlencode(Foundation::assetVersion()), $url);
    }

    public function test_no_package_view_loads_a_published_asset_without_the_version(): void
    {
        $plain = [];

        foreach ([Foundation::uiPath('resources/views'), Foundation::modulesPath()] as $root) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), '.blade.php')
                    && preg_match('/(?<!foundation_)asset\(\s*[\'"]assets\//', (string) file_get_contents($file->getPathname())) === 1) {
                    $plain[] = $file->getFilename();
                }
            }
        }

        $this->assertSame([], $plain);
    }

    public function test_the_icon_fonts_are_requested_with_their_version(): void
    {
        foreach (['phosphor.css', 'phosphor-light.css', 'phosphor-thin.css', 'phosphor-duotone.css'] as $sheet) {
            $css = (string) file_get_contents(Foundation::uiPath('public/assets/icons/phosphor/'.$sheet));
            preg_match_all('/url\("([^"]+)"\)/', $css, $urls);

            $this->assertNotEmpty($urls[1], $sheet);
            foreach ($urls[1] as $url) {
                $this->assertMatchesRegularExpression('/\?v=\d+\.\d+\.\d+$/', $url, "{$sheet}: {$url}");
            }
        }
    }
}
