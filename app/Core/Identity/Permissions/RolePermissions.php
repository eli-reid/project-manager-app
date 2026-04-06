<?php

namespace App\Core\Identity\Permissions;

class RolePermissions
{
    public const VIEW_ROLES = [
        'resource' => 'roles',
        'action' => 'view',
        'description' => 'View all roles',
    ];

    public const CREATE_ROLES = [
        'resource' => 'roles',
        'action' => 'create',
        'description' => 'Create new roles',
    ];

    public const EDIT_ROLES = [
        'resource' => 'roles',
        'action' => 'edit',
        'description' => 'Edit existing roles',
    ];

    public const DELETE_ROLES = [
        'resource' => 'roles',
        'action' => 'delete',
        'description' => 'Delete roles from the system',
    ];

    public const ASSIGN_PERMISSIONS = [
        'resource' => 'roles',
        'action' => 'assign-permissions',
        'description' => 'Assign permissions to roles',
    ];

    public static function all(): array
    {
        return [
            self::VIEW_ROLES,
            self::CREATE_ROLES,
            self::EDIT_ROLES,
            self::DELETE_ROLES,
            self::ASSIGN_PERMISSIONS,
        ];
    }
}
