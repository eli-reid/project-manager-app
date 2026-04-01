<?php

namespace App\Core\Queue\Permissions;

class QueuePermissions
{
    public const VIEW = [
        'resource' => 'queue',
        'action' => 'view',
        'description' => 'View queue manager',
    ];

    public const MANAGE = [
        'resource' => 'queue',
        'action' => 'manage',
        'description' => 'Retry and clear queue jobs',
    ];

    public static function all(): array
    {
        return [
            self::VIEW,
            self::MANAGE,
        ];
    }
}
