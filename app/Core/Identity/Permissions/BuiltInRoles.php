<?php

namespace App\Core\Identity\Permissions;

class BuiltInRoles
{
    public const SYSTEM_ADMIN = 'system_admin';

    public const ADMIN = 'admin';

    public const USER = 'user';

    /**
     * Get all built-in roles.
     */
    public static function all(): array
    {
        return [
            self::SYSTEM_ADMIN,
            self::ADMIN,
            self::USER,
        ];
    }

    /**
     * Get permissions for each built-in role.
     */
    public static function permissions(): array
    {
        return [
            self::SYSTEM_ADMIN => [
                // All user and role permissions
                'user.create',
                'user.read',
                'user.update',
                'user.delete',
                'role.create',
                'role.read',
                'role.update',
                'role.delete',
                'role.assign',
                'role.revoke',
                // Add more as needed
            ],
            self::ADMIN => [
                // Fill in admin permissions as needed
            ],
            self::USER => [
                // Fill in user permissions as needed
            ],
        ];
    }
}
