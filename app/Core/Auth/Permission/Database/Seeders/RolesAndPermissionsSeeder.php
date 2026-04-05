<?php

namespace App\Core\Auth\Permission\Database\Seeders;

use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var DomainPermissionSynchronizer $synchronizer */
        $synchronizer = app(DomainPermissionSynchronizer::class);
        $synchronizer->sync();
    }
}
