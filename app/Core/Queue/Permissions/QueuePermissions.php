<?php

namespace App\Core\Queue\Permissions;

class QueuePermissions
{
    public const MANAGE_QUEUE = [
        'resource' => 'queue',
        'action' => 'manage',
        'label' => 'Manage Queue',
        'description' => 'Manage job queue, retry, and flush operations',
        'built_in_roles' => ['Admin'],
    ];

    public const VIEW_QUEUE = [
        'resource' => 'queue',
        'action' => 'view',
        'label' => 'View Queue',
        'description' => 'View queue monitoring dashboard and job status',
        'built_in_roles' => ['Admin'],
    ];

    public static function all(): array
    {
        return [
            self::MANAGE_QUEUE,
            self::VIEW_QUEUE,
        ];
    }
}
