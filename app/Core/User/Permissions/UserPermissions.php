<?php

namespace App\Core\User\Permissions;

class UserPermissions
{
    public const VIEW_USERS = [
        'resource' => 'users',
        'action' => 'view',
        'description' => 'View the list of users',
    ];

    public const CREATE_USERS = [
        'resource' => 'users',
        'action' => 'create',
        'description' => 'Create new users',
    ];

    public const EDIT_USERS = [
        'resource' => 'users',
        'action' => 'edit',
        'description' => 'Edit existing users',
    ];

    public const DELETE_USERS = [
        'resource' => 'users',
        'action' => 'delete',
        'description' => 'Delete users from the system',
    ];

    public const UPDATE_PASSWORD = [
        'resource' => 'users',
        'action' => 'update-password',
        'description' => 'Reset or update a user’s password',
    ];

    public static function all(): array
    {
        return [
            self::VIEW_USERS,
            self::CREATE_USERS,
            self::EDIT_USERS,
            self::DELETE_USERS,
            self::UPDATE_PASSWORD,
        ];
    }
}

