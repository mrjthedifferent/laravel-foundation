<?php

namespace Modules\Settings\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Actions\SyncSettingsAction;
use Modules\Settings\Models\Setting;
use Tests\TestCase;

class SyncSettingsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_settings_declared_by_the_enabled_modules(): void
    {
        Setting::query()->delete();

        app(SyncSettingsAction::class)->execute();

        // One from the Settings module, one from another module's config/settings.php.
        $this->assertDatabaseHas('settings', ['key' => 'app_name']);
        $this->assertDatabaseHas('settings', ['key' => 'error_report_enabled']);
    }

    public function test_it_never_overwrites_a_value_that_was_already_saved(): void
    {
        app(SyncSettingsAction::class)->execute();
        Setting::where('key', 'app_name')->sole()->update(['value' => 'My Product']);

        app(SyncSettingsAction::class)->execute();

        $this->assertSame('My Product', Setting::where('key', 'app_name')->sole()->value);
        $this->assertSame(1, Setting::where('key', 'app_name')->count());
    }

    public function test_it_leaves_custom_settings_alone(): void
    {
        $custom = Setting::factory()->create(['key' => 'project_custom_flag', 'value' => 'kept']);

        app(SyncSettingsAction::class)->execute();

        $this->assertSame('kept', $custom->fresh()->value);
    }
}
