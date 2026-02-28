<?php

namespace App\Core\User\Permissions;

class PermissionPermissions
{
    public const VIEW_PERMISSIONS = [
        'resource' => 'permissions',
        'action' => 'view',
        'description' => 'View the list of permissions',
    ];

    public static function all(): array
    {
        return [
            self::VIEW_PERMISSIONS,
        ];
    }
}

