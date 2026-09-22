<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class UiTest extends TestCase
{
    public function test_dashboard_renders_in_the_app_layout(): void
    {
        // No permissions, so every module's contribution must degrade to nothing.
        $this->actingAs(User::factory()->create(['name' => 'Ada Lovelace']))
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertSee(route('logout'))
            ->assertDontSee('fd-stat-value', false)
            ->assertDontSee('chart-area', false)
            ->assertDontSee('fd-feed', false);
    }

    public function test_guest_layout_renders(): void
    {
        $html = Blade::render('<x-guest-layout>Sign in here</x-guest-layout>');

        $this->assertStringContainsString('Sign in here', $html);
    }

    #[DataProvider('components')]
    public function test_component_renders(string $blade): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertNotSame('', trim(Blade::render($blade, ['rows' => collect()])));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function components(): array
    {
        return [
            'page-header' => ['<x-page-header title="Things" icon="ph-cube" back-url="/back" />'],
            'form-section' => ['<x-form-section title="Details">body</x-form-section>'],
            'search-card' => ['<x-search-card>filters</x-search-card>'],
            'table-view-pagination' => ['<x-table-view-pagination title="Things" :data="$rows"><tr><td>x</td></tr></x-table-view-pagination>'],
            'stat-card' => ['<x-stat-card label="Users" value="3" change="+12%" />'],
            'modal' => ['<x-modal id="edit" title="Edit">body</x-modal>'],
            'dropdown' => ['<x-dropdown-menu><x-dropdown-link url="/x">Open</x-dropdown-link></x-dropdown-menu>'],
            'buttons' => ['<x-primary-button>Save</x-primary-button><x-secondary-button>No</x-secondary-button><x-danger-button>Del</x-danger-button><x-link-button href="/x">Go</x-link-button>'],
            'inputs' => ['<x-input-label value="Name" /><x-text-input name="name" /><x-input-error :messages="[\'Required\']" />'],
            'status-badge' => ['<x-status-badge :active="true" />'],
            'alert' => ['<x-alert type="success">Saved</x-alert>'],
            'truncated-text' => ['<x-truncated-text text="A long piece of text" :limit="5" />'],
            'image' => ['<x-image src="/images/default.png" alt="x" />'],
        ];
    }

    public function test_a_project_view_overrides_the_foundation_view(): void
    {
        $override = resource_path('views/components/page-header.blade.php');
        File::ensureDirectoryExists(dirname($override));
        File::put($override, '<h1>project header</h1>');

        try {
            $this->assertStringContainsString('project header', Blade::render('<x-page-header title="ignored" />'));
        } finally {
            File::delete($override);
        }
    }

    public function test_every_shipped_view_compiles(): void
    {
        $compiler = app('blade.compiler');

        foreach (File::allFiles(Foundation::uiPath('resources/views')) as $file) {
            $compiler->compile($file->getPathname());
            $this->assertFileExists($compiler->getCompiledPath($file->getPathname()));
        }
    }
}
