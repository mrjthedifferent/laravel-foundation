<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Mrj\Foundation\Database\Seeders\FoundationSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Permissions, settings, base roles and the first Super Admin (SEED_ADMIN_* in .env).
        $this->call(FoundationSeeder::class);

        // The project's own seeders follow.
    }
}
