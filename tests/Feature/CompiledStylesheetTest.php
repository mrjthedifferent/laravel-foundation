<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;

/**
 * The stylesheet is compiled once inside the package, so a class a project may use has to be in it.
 * The classes named here are the documented vocabulary (theming.md): if one is missing, the safelist
 * in ui/resources/css/safelist.css lost it.
 */
class CompiledStylesheetTest extends TestCase
{
    private function css(): string
    {
        return (string) file_get_contents(Foundation::path('ui/public/assets/css/foundation.css'));
    }

    public function test_the_documented_utility_vocabulary_is_compiled_in(): void
    {
        $css = $this->css();

        foreach ([
            '.mt-5{', '.px-2{', '.ms-auto{', '.gap-x-5{', '.flex{', '.grid-cols-12{', '.md\:col-span-6{', '.xl\:col-span-3{', '.lg\:grid-cols-4{',
            '.md\:w-1\/3{', '.max-w-3xl{', '.z-50{', '.text-muted{', '.bg-danger-subtle{', '.border-line{', '.rounded-xl{', '.shadow-md{',
            '.hover\:bg-hover:hover{', '.print\:hidden{', '.sr-only{', '.truncate{',
        ] as $selector) {
            $this->assertStringContainsString($selector, $css, "foundation.css lacks {$selector}");
        }
    }

    /**
     * An open dropdown is moved to <body> and fixed in place; it must stack above the sticky
     * navbar (1030) and the sidebar (1035), or the profile menu's top is hidden under the bar,
     * and below offcanvas (1040) and modals.
     */
    public function test_a_placed_dropdown_menu_stacks_above_the_navbar_and_sidebar(): void
    {
        $this->assertMatchesRegularExpression('/\.dropdown-menu\[data-placed\]\{[^}]*z-index:(\d+)/', $this->css());
        preg_match('/\.dropdown-menu\[data-placed\]\{[^}]*z-index:(\d+)/', $this->css(), $placed);

        $this->assertGreaterThan(1035, (int) $placed[1]);
        $this->assertLessThan(1040, (int) $placed[1]);
    }

    public function test_the_stylesheet_carries_no_bootstrap(): void
    {
        $css = $this->css();

        $this->assertStringNotContainsString('--bs-', $css);
        $this->assertStringNotContainsString('data-bs-theme', $css);
        $this->assertStringContainsString('tailwindcss v4', $css);
        $this->assertFileDoesNotExist(Foundation::path('ui/public/assets/css/bootstrap.min.css'));
        $this->assertFileDoesNotExist(Foundation::path('ui/public/assets/js/bootstrap.bundle.min.js'));
    }
}
