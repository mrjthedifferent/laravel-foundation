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
                if (! Tenancy::allows($value['contexts'] ?? null)) {
                    continue;
                }

                unset($value['contexts']);
                $value['key'] = $key;
                Setting::firstOrCreate(['key' => $key], $value);
            }
        }
    }
}
