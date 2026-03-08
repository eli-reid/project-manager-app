<?php

namespace App\Core\Scheduler\Permissions;

class SchedulerPermissions
{
    public const VIEW_TASKS = [
        'resource' => 'scheduler',
        'action' => 'view',
        'description' => 'View scheduler tasks',
    ];

    public const CREATE_TASKS = [
        'resource' => 'scheduler',
        'action' => 'create',
        'description' => 'Create scheduler tasks',
    ];

    public const EDIT_TASKS = [
        'resource' => 'scheduler',
        'action' => 'edit',
        'description' => 'Edit scheduler tasks',
    ];

    public const DELETE_TASKS = [
        'resource' => 'scheduler',
        'action' => 'delete',
        'description' => 'Delete scheduler tasks',
    ];

    public const TOGGLE_TASKS = [
        'resource' => 'scheduler',
        'action' => 'toggle',
        'description' => 'Enable or disable scheduler tasks',
    ];

    public const RUN_TASKS = [
        'resource' => 'scheduler',
        'action' => 'run',
        'description' => 'Run scheduler tasks manually',
    ];

    public static function all(): array
    {
        return [
            self::VIEW_TASKS,
            self::CREATE_TASKS,
            self::EDIT_TASKS,
            self::DELETE_TASKS,
            self::TOGGLE_TASKS,
            self::RUN_TASKS,
        ];
    }
}
