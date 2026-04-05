<?php

namespace App\Core\Database\Seeders;

use App\Core\Auth\Permission\Database\Seeders\RolesAndPermissionsSeeder;
use App\Core\Auth\User\Database\Seeders\UserSeeder;
use App\Core\Settings\Database\Seeders\SettingsSeeder;
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
