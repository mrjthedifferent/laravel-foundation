<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mrj\Foundation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every module's layouts/master.blade.php was a near-identical copy of
 * <x-app-layout> plus a breadcrumb, differing only in one route name and
 * label; all 9 now delegate to this one component instead. Rendering each
 * module's master layout standalone (with no child @section content, as a
 * page normally supplies) is enough to prove the component resolves the
 * given route and compiles without error for every module.
 */
class ModuleLayoutComponentTest extends TestCase
{
    use RefreshDatabase;

    public static function moduleLayouts(): array
    {
        return [
            'ActivityLog' => ['activitylog::layouts.master'],
            'BackupCleanup' => ['backupcleanup::layouts.master'],
            'ErrorReport' => ['errorreport::layouts.master'],
            'ImportDownloadManager' => ['importdownloadmanager::layouts.master'],
            'Notification' => ['notification::layouts.master'],
            'Otp' => ['otp::layouts.master'],
            'RolePermission' => ['rolepermission::layouts.master'],
            'Settings' => ['settings::layouts.master'],
            'User' => ['user::layouts.master'],
        ];
    }

    #[DataProvider('moduleLayouts')]
    public function test_module_layout_renders(string $view): void
    {
        $this->actingAs(User::factory()->create());

        $html = view($view)->render();

        $this->assertStringContainsString('breadcrumb-item', $html);
    }
}
