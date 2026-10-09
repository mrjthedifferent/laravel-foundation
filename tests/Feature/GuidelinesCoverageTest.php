<?php

namespace Mrj\Foundation\Tests\Feature;

use FilesystemIterator;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The guidelines are what an AI assistant (or a new developer) reads to reuse the package, so they
 * must name everything the package offers. These checks fail when a component, extension point or
 * guideline file is added without being documented.
 */
class GuidelinesCoverageTest extends TestCase
{
    private function guideline(string $name): string
    {
        return (string) file_get_contents(Foundation::path('sync/guidelines/'.$name));
    }

    /**
     * @return list<string> e.g. "page-header", "form.input"
     */
    private function componentTags(): array
    {
        $root = Foundation::path('ui/resources/views/components');
        $tags = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            $relative = str_replace(['\\', '.blade.php'], ['/', ''], substr($file->getPathname(), strlen($root) + 1));
            $tags[] = str_replace('/', '.', $relative);
        }

        return $tags;
    }

    public function test_every_blade_component_is_in_the_components_reference(): void
    {
        $reference = $this->guideline('components-reference.md');
        $missing = array_values(array_filter(
            $this->componentTags(),
            fn (string $tag): bool => ! str_contains($reference, '<x-'.$tag) && ! str_contains($reference, '`x-'.$tag),
        ));

        // Layout wrappers are documented as a group, and a component class has no view file of its own.
        $this->assertSame([], $missing, 'Document these in sync/guidelines/components-reference.md: '.implode(', ', $missing));
    }

    public function test_every_module_dashboard_extension_point_is_in_the_dashboard_guide(): void
    {
        $guide = $this->guideline('dashboard.md');
        $source = (string) file_get_contents(Foundation::path('src/Support/ModuleServiceProvider.php'));

        preg_match_all('/protected array \$(dashboard\w+)\b/', $source, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $property) {
            $this->assertStringContainsString('$'.$property, $guide, "dashboard.md does not mention \${$property}");
        }
    }

    public function test_every_guideline_file_is_reachable_from_the_overview(): void
    {
        $overview = $this->guideline('foundation-overview.md');

        foreach (glob(Foundation::path('sync/guidelines/*.md')) as $path) {
            $name = basename($path);

            if ($name === 'foundation-overview.md') {
                continue;
            }

            $this->assertStringContainsString($name, $overview, "foundation-overview.md does not link {$name}");
        }
    }

    public function test_the_guidelines_do_not_teach_bootstrap_markup(): void
    {
        foreach (glob(Foundation::path('sync/guidelines/*.md')) as $path) {
            $text = (string) file_get_contents($path);

            // Mentioned only to say it is gone: the overview, the JS guide, the anti-pattern table and the upgrade note.
            if (in_array(basename($path), ['foundation-overview.md', 'frontend-js.md'], true)) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression('/data-bs-(toggle|target|dismiss)\b/', $text, basename($path));
            $this->assertDoesNotMatchRegularExpression('/\b(text|bg)-(success|danger|warning|info|secondary)-emphasis\b/', $text, basename($path));
            $this->assertDoesNotMatchRegularExpression('/var\(--bs-/', $text, basename($path));
            $this->assertStringNotContainsString('class="row', $text, basename($path));
        }
    }
}
