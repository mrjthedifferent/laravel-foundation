<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Settings\Models\Setting;
use Nwidart\Modules\Facades\Module;

class SettingsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Iterates over all enabled modules and seeds any settings defined
     * in their config/settings.php file into the settings table.
     * Modules with no settings simply return an empty array.
     */
    public function run(): void
    {
        foreach (Module::allEnabled() as $module) {
            $settingsConfig = $module->getPath().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'settings.php';

            if (! File::exists($settingsConfig)) {
                continue;
            }

            $settings = require $settingsConfig;

            foreach ($settings as $key => $value) {
                $value['key'] = $key;
                Setting::firstOrCreate(['key' => $key], $value);
            }
        }
    }
}
