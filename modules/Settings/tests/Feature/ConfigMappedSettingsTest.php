<?php

namespace Modules\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Database\Seeders\SettingsSettingsSeeder;
use Modules\Settings\Models\Setting;
use Modules\Settings\Support\SettingsConfigApplier;
use Mrj\Foundation\Contracts\SettingsRepository;
use Tests\TestCase;

class ConfigMappedSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function apply(): void
    {
        app(SettingsRepository::class)->forget();
        app(SettingsConfigApplier::class)->apply();
    }

    public function test_the_map_comes_from_the_setting_definitions(): void
    {
        $map = app(SettingsConfigApplier::class)->map();

        $this->assertSame('services.google.client_id', $map['google_client_id']);
        $this->assertSame('foundation.formats.date', $map['date_format']);
        $this->assertSame('foundation.passwords.min_length', $map['password_min_length']);
        $this->assertArrayNotHasKey('app_name', $map);
    }

    public function test_the_seeder_takes_unset_values_from_config_and_skips_page_owned_settings(): void
    {
        config(['foundation.formats.date' => 'd/m/Y', 'foundation.pagination.default' => 25]);

        $this->seed(SettingsSettingsSeeder::class);

        $this->assertSame('d/m/Y', Setting::where('key', 'date_format')->first()->getRawOriginal('value'));
        $this->assertSame('25', Setting::where('key', 'pagination_default')->first()->getRawOriginal('value'));
        $this->assertDatabaseMissing('settings', ['key' => 'password_min_length']);
        $this->assertDatabaseMissing('settings', ['key' => 'two_factor_enabled']);
        $this->assertNull(Setting::where('key', 'date_format')->first()->getAttribute('config'));
    }

    public function test_a_stored_value_overrides_config_and_null_leaves_it(): void
    {
        Setting::create(['key' => 'date_format', 'group' => 'General', 'type' => 'text', 'value' => 'd M Y']);
        Setting::create(['key' => 'datetime_format', 'group' => 'General', 'type' => 'text', 'value' => null]);
        $datetime = config('foundation.formats.datetime');

        $this->apply();

        $this->assertSame('d M Y', config('foundation.formats.date'));
        $this->assertSame($datetime, config('foundation.formats.datetime'));
    }

    public function test_the_default_page_size_setting_is_used(): void
    {
        Setting::create(['key' => 'pagination_default', 'group' => 'General', 'type' => 'select', 'value' => '25']);

        $this->apply();

        $this->assertSame(25, perPage());
    }
}
