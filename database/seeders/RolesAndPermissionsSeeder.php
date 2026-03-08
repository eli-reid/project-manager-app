<?php

namespace Database\Seeders;

use App\Core\User\Services\DomainPermissionSynchronizer;
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
