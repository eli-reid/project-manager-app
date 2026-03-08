<?php

namespace App\Domains\Documents\Providers;

use App\Core\User\Services\PermissionRegistry;
use Illuminate\Support\ServiceProvider;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions([
            [
                'resource' => 'documents',
                'action' => 'view',
                'label' => 'View Documents',
                'description' => 'View project documents',
                'built_in_roles' => ['User'],
            ],
            [
                'resource' => 'documents',
                'action' => 'create',
                'label' => 'Upload Documents',
                'description' => 'Upload new project documents',
            ],
            [
                'resource' => 'documents',
                'action' => 'update',
                'label' => 'Update Documents',
                'description' => 'Rename and update project documents',
            ],
            [
                'resource' => 'documents',
                'action' => 'delete',
                'label' => 'Delete Documents',
                'description' => 'Delete project documents',
            ],
        ]);
    }
}
