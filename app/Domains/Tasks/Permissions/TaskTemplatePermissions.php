<?php

namespace App\Domains\Tasks\Permissions;

class TaskTemplatePermissions
{
    public const VIEW = [
        'resource' => 'task-templates',
        'action' => 'view',
        'description' => 'View task templates',
    ];

    public const CREATE = [
        'resource' => 'task-templates',
        'action' => 'create',
        'description' => 'Create task templates',
    ];

    public const EDIT = [
        'resource' => 'task-templates',
        'action' => 'edit',
        'description' => 'Edit task templates',
    ];

    public const DELETE = [
        'resource' => 'task-templates',
        'action' => 'delete',
        'description' => 'Delete task templates',
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
