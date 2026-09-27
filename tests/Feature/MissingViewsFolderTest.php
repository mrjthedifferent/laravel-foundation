<?php

namespace Mrj\Foundation\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;
use Mrj\Foundation\Tests\TestCase;
use Override;

/**
 * A project with no resources/views of its own: the path is set in
 * resolveApplicationConfiguration(), before the view finder is built from it.
 */
class MissingViewsFolderTest extends TestCase
{
    private const string MISSING = __DIR__.'/does-not-exist/views';

    #[Override]
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('view.paths', [self::MISSING]);
    }

    public function test_a_missing_views_folder_is_left_out_of_the_view_paths(): void
    {
        $this->assertNotContains(self::MISSING, View::getFinder()->getPaths());
        $this->assertNotContains(self::MISSING, config('view.paths'));
    }

    public function test_views_can_be_cached_without_a_views_folder(): void
    {
        $this->assertSame(0, Artisan::call('view:cache'));

        Artisan::call('view:clear');
    }
}
