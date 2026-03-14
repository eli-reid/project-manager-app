<?php

namespace App\Domains\Projects\Permissions;

class ProjectPermissions
{
    public const VIEW = [
        'resource' => 'projects',
        'action' => 'view',
        'description' => 'View projects',
    ];

    public const CREATE = [
        'resource' => 'projects',
        'action' => 'create',
        'description' => 'Create projects',
    ];

    public const EDIT = [
        'resource' => 'projects',
        'action' => 'edit',
        'description' => 'Edit projects',
    ];

    public const DELETE = [
        'resource' => 'projects',
        'action' => 'delete',
        'description' => 'Delete projects',
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
