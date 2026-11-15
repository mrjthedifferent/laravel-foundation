<?php

namespace Mrj\Foundation\Tests\Feature;

use FilesystemIterator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Components that print a prop with {{ }} escape it themselves, so the text must reach them
 * bound (:title="__('…')"). Passed as title="{{ __('…') }}", Blade escapes it once on the way
 * in and the component again on the way out: "Terms &amp;amp; Conditions".
 */
class ComponentEscapingTest extends TestCase
{
    private const string TEXT = "Terms & Conditions for the application's users";

    private const string ONCE = 'Terms &amp; Conditions for the application&#039;s users';

    /**
     * The props each component prints with {{ }}.
     *
     * @var array<string, list<string>>
     */
    private const array ESCAPED_PROPS = [
        'page-header' => ['title', 'subtitle', 'back-label'],
        'form-section' => ['title'],
        'form.file' => ['label'],
        'form.input' => ['label'],
        'form.select' => ['label'],
        'form.textarea' => ['label'],
        'input-label' => ['value'],
        'modal' => ['title'],
        'empty-state' => ['title', 'text'],
        'table-export-item' => ['title'],
        'table-view-pagination' => ['title', 'empty-message'],
        'image' => ['alt'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        View::share('errors', new ViewErrorBag);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function bladeUsing(): array
    {
        return [
            'page-header title' => ['<x-page-header :title="$text" />'],
            'page-header subtitle' => ['<x-page-header title="Page" :subtitle="$text" />'],
            'page-header back-label' => ['<x-page-header title="Page" back-url="/" :back-label="$text" />'],
            'form-section title' => ['<x-form-section :title="$text">Body</x-form-section>'],
            'form.input label' => ['<x-form.input name="name" :label="$text" />'],
            'form.select label' => ['<x-form.select name="type" :label="$text" :options="[]" />'],
            'form.textarea label' => ['<x-form.textarea name="notes" :label="$text" />'],
            'form.file label' => ['<x-form.file name="file" :label="$text" />'],
            'modal title' => ['<x-modal id="m" :title="$text">Body</x-modal>'],
            'empty-state title' => ['<x-empty-state :title="$text" />'],
            'table-view-pagination title' => ['<x-table-view-pagination :title="$text" :data="collect()" />'],
            'table-view-pagination empty-message' => ['<x-table-view-pagination title="List" :data="collect()" :empty-message="$text" />'],
            'table-export-item title' => ['<x-table-export-item href="#" :title="$text" />'],
        ];
    }

    #[DataProvider('bladeUsing')]
    public function test_bound_text_is_escaped_exactly_once(string $blade): void
    {
        $html = Blade::render($blade, ['text' => self::TEXT]);

        $this->assertStringContainsString(self::ONCE, $html);
        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertStringNotContainsString('&amp;#039;', $html);
    }

    public function test_the_package_views_never_pass_echoed_text_to_a_prop_the_component_escapes(): void
    {
        $offenders = [];

        foreach ([Foundation::path('ui/resources/views'), Foundation::path('modules')] as $root) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
                $path = $file->getPathname();
                if (! str_ends_with($path, '.blade.php')) {
                    continue;
                }

                $source = (string) file_get_contents($path);
                preg_match_all('/<x-([\w.:-]+)((?:\s+[^\s=>\/]+(?:=(?:"[^"]*"|\'[^\']*\'))?)*)\s*\/?>/s', $source, $tags, PREG_SET_ORDER);

                foreach ($tags as [, $component, $attributes]) {
                    foreach (self::ESCAPED_PROPS[$component] ?? [] as $prop) {
                        if (preg_match('/\s'.preg_quote($prop, '/').'="[^"]*\{\{/', $attributes)) {
                            $offenders[] = ltrim(str_replace('\\', '/', substr($path, strlen(Foundation::path()))), '/').": <x-{$component} {$prop}=\"{{ … }}\">";
                        }
                    }
                }
            }
        }

        $this->assertSame([], $offenders, 'Bind these instead (:'.'prop="__(…)"), or the text is escaped twice');
    }
}
