<?php

namespace App\Core\Database\Seeders;

use App\Core\Settings\Database\Seeders\SettingsSeeder;
use App\Core\User\Database\Seeders\RolesAndPermissionsSeeder;
use App\Core\User\Database\Seeders\UserSeeder;
use Illuminate\Database\Seeder;

/**
 * Seeds all core application data that should run on every fresh install.
 */
class CoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(SettingsSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserSeeder::class);
    }
}
