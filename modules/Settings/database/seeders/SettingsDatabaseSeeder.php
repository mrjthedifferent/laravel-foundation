<?php

declare(strict_types=1);

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SettingsSettingsSeeder::class,
        ]);
    }
}
