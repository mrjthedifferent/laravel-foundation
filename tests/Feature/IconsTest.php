<?php

namespace Mrj\Foundation\Tests\Feature;

use FilesystemIterator;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Icons are Phosphor 2 font glyphs: a name that is not in the shipped stylesheet renders as an empty box,
 * silently. Every icon the package writes must exist there.
 */
class IconsTest extends TestCase
{
    /** Classes that start with "ph-" but are helpers or weights, not icons. */
    private const array NOT_ICONS = ['sm', 'lg', '2x', '3x', 'spin', 'thin', 'light', 'bold', 'fill', 'duotone'];

    /**
     * @return array<string, true>
     */
    private function shipped(): array
    {
        $css = (string) file_get_contents(Foundation::path('ui/public/assets/icons/phosphor/phosphor.css'));
        preg_match_all('/\.ph(?:-(?:bold|fill))?\.ph-([a-z0-9-]+):+before/', $css, $matches);

        return array_fill_keys($matches[1], true);
    }

    public function test_the_icon_stylesheet_is_phosphor_2_with_regular_bold_and_fill(): void
    {
        $css = (string) file_get_contents(Foundation::path('ui/public/assets/icons/phosphor/phosphor.css'));

        $this->assertStringContainsString('Phosphor Icons 2.', $css);
        foreach (['.ph{', '.ph-bold{', '.ph-fill{'] as $weight) {
            $this->assertStringContainsString($weight, $css);
        }
        foreach (['light', 'thin', 'duotone'] as $weight) {
            $this->assertFileExists(Foundation::path("ui/public/assets/icons/phosphor/phosphor-{$weight}.css"));
        }
        $this->assertGreaterThan(1000, count($this->shipped()));
    }

    public function test_every_icon_the_package_uses_exists(): void
    {
        $shipped = $this->shipped();
        $missing = [];

        foreach (['ui/resources/views', 'ui/resources/js', 'modules', 'src', 'config'] as $dir) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(Foundation::path($dir), FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (! preg_match('/\.(php|js)$/', $file->getFilename()) || str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR)) {
                    continue;
                }
                preg_match_all('/(?<![\w.-])ph-([a-z0-9]+(?:-[a-z0-9]+)*)(?![\w-])/', (string) file_get_contents($file->getPathname()), $matches);
                foreach ($matches[1] as $name) {
                    if (! in_array($name, self::NOT_ICONS, true) && ! isset($shipped[$name])) {
                        $missing[] = 'ph-'.$name.' in '.str_replace(Foundation::path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }
}
