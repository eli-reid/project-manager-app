<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * CoreSeeder
 *
 * Seeds all core application data that should run on every fresh install.
 * Run with: php artisan db:seed --class=CoreSeeder
 *
 * Child seeders:
 * - SettingsSeeder: Default application settings
 * - UserSeeder: Default built-in and test users
 */
class CoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed default settings
        $this->call(SettingsSeeder::class);

        // Seed roles and permissions registered by core/domains
        $this->call(RolesAndPermissionsSeeder::class);

        // Seed default users
        $this->call(UserSeeder::class);

        // Add more core seeders here as needed:
        // $this->call(RolesAndPermissionsSeeder::class);
        // $this->call(DefaultUsersSeeder::class);
    }
}
