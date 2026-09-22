<?php

namespace Mrj\Foundation\Tests\Unit;

use App\Models\User;
use Illuminate\View\View;
use Mockery;
use Modules\User\View\Composers\UserWidgetComposer;
use Mrj\Foundation\Tests\TestCase;
use Spatie\Permission\Models\Permission;

class WidgetComposerTest extends TestCase
{
    public function test_it_withholds_the_widget_from_a_viewer_without_permission(): void
    {
        $this->actingAs(User::factory()->create());

        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with('widget', null);

        app(UserWidgetComposer::class)->compose($view);
    }

    public function test_it_supplies_data_to_a_viewer_with_permission(): void
    {
        Permission::create(['name' => 'View User', 'guard_name' => 'web', 'module_name' => 'User']);
        $user = User::factory()->create();
        $user->givePermissionTo('View User');
        $this->actingAs($user);

        $view = Mockery::mock(View::class);
        $view->shouldReceive('with')->once()->with('widget', Mockery::on(
            fn ($data) => is_array($data) && array_key_exists('total_users', $data)
        ));

        app(UserWidgetComposer::class)->compose($view);
    }
}
