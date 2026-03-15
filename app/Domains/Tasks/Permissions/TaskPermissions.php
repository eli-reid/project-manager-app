<?php

namespace App\Domains\Tasks\Permissions;

class TaskPermissions
{
    public const VIEW = [
        'resource' => 'tasks',
        'action' => 'view',
        'description' => 'View tasks',
    ];

    public const CREATE = [
        'resource' => 'tasks',
        'action' => 'create',
        'description' => 'Create tasks',
    ];

    public const EDIT = [
        'resource' => 'tasks',
        'action' => 'edit',
        'description' => 'Edit tasks',
    ];

    public const DELETE = [
        'resource' => 'tasks',
        'action' => 'delete',
        'description' => 'Delete tasks',
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
