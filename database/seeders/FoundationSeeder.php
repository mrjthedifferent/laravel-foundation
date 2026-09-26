<?php

namespace Mrj\Foundation\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RolePermission\Database\Seeders\RolePermissionPermissionsSeeder;
use Mrj\Foundation\Foundation;
use Nwidart\Modules\Facades\Module;

/**
 * Seeds everything the foundation needs, in dependency order. Call it first
 * from the project's DatabaseSeeder; safe to run again after enabling a module
 * or adding permissions and settings.
 */
class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions are collected from every enabled module's config, the
        // project's own modules included. Settings are collected the same way,
        // by SettingsDatabaseSeeder in the loop below.
        $this->call(RolePermissionPermissionsSeeder::class);

        $foundationModules = str_replace('\\', '/', Foundation::modulesPath()).'/';

        // The project seeds its own modules; only the foundation's are run here.
        foreach (Module::getOrdered() as $module) {
            $seeder = 'Modules\\'.$module->getName().'\\Database\\Seeders\\'.$module->getName().'DatabaseSeeder';
            $path = str_replace('\\', '/', $module->getPath()).'/';

            if (str_starts_with($path, $foundationModules) && class_exists($seeder)) {
                $this->call($seeder);
            }
        }
    }
}
