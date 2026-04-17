<?php

namespace Database\Seeders;

use App\Core\Database\Seeders\CoreSeeder;
use App\Domains\Database\Seeders\DomainDemoSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CoreSeeder::class);

        if ((bool) config('database.seed_demo_data', false)) {
            $this->call(DomainDemoSeeder::class);
        }
    }
}
