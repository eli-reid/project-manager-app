<?php

namespace App\Domains\Addresses\Permissions;

class AddressPermissions
{
    public const VIEW = [
        'resource' => 'addresses',
        'action' => 'view',
        'description' => 'View addresses',
    ];

    public const CREATE = [
        'resource' => 'addresses',
        'action' => 'create',
        'description' => 'Create addresses',
    ];

    public const EDIT = [
        'resource' => 'addresses',
        'action' => 'edit',
        'description' => 'Edit addresses',
    ];

    public const DELETE = [
        'resource' => 'addresses',
        'action' => 'delete',
        'description' => 'Delete addresses',
    ];

    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
        ];
    }
}
