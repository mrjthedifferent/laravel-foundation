<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Support\Tenancy;
use Nwidart\Modules\Facades\Module;

class SettingsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Iterates over all enabled modules and seeds any settings defined
     * in their config/settings.php file into the settings table.
     * Modules with no settings simply return an empty array.
     *
     * With foundation.tenancy enabled, only the modules that belong where the
     * seeder runs (the central database or a tenant's) contribute, and a
     * setting carrying 'contexts' => ['central'] (or ['tenant']) is seeded
     * only there, as permissions are.
     *
     * 'config' => 'some.config.key' makes SettingsConfigApplier copy the value
     * onto that config key; 'seed' => false leaves the row to be created by
     * the page that manages it.
     */
    public function run(): void
    {
        foreach (Module::allEnabled() as $module) {
            if (! Tenancy::moduleBelongsHere($module)) {
                continue;
            }

            $settingsConfig = $module->getPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'settings.php';

            if (! File::exists($settingsConfig)) {
                continue;
            }

            $settings = require $settingsConfig;

            foreach ($settings as $key => $value) {
                // 'seed' => false: the row is created by the page that manages it, on
                // its first save; until then the config (and .env) decides.
                if (! Tenancy::allows($value['contexts'] ?? null) || ($value['seed'] ?? true) === false) {
                    continue;
                }

                // A mapped setting without a 'value' starts from what the config (and
                // .env) says now, so seeding it into an existing project changes nothing.
                if (isset($value['config']) && ! array_key_exists('value', $value)) {
                    $value['value'] = self::storable(config($value['config']));
                }

                // 'config' is read by SettingsConfigApplier from the definition, not stored.
                unset($value['contexts'], $value['seed'], $value['config']);
                $value['key'] = $key;
                Setting::firstOrCreate(['key' => $key], $value);
            }
        }
    }

    /**
     * A config value in the form the settings table stores it.
     */
    private static function storable(mixed $value): ?string
    {
        return match (true) {
            is_bool($value) => $value ? '1' : '0',
            is_array($value) => implode(',', $value),
            $value === null => null,
            default => (string) $value,
        };
    }
}
