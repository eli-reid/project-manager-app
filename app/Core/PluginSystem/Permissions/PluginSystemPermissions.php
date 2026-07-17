<?php

namespace App\Core\PluginSystem\Permissions;

class PluginSystemPermissions
{
    public const VIEW = [
        'resource' => 'plugins',
        'action' => 'view',
        'description' => 'View plugin inventory and security posture',
    ];

    public const INSTALL = [
        'resource' => 'plugins',
        'action' => 'install',
        'description' => 'Stage plugin installs from approved sources',
    ];

    public const REVIEW = [
        'resource' => 'plugins',
        'action' => 'review',
        'description' => 'Review and approve plugin security findings',
    ];

    public const ENABLE = [
        'resource' => 'plugins',
        'action' => 'enable',
        'description' => 'Enable an installed plugin after review',
    ];

    public const DISABLE = [
        'resource' => 'plugins',
        'action' => 'disable',
        'description' => 'Disable or quarantine an installed plugin',
    ];

    public const DELETE = [
        'resource' => 'plugins',
        'action' => 'delete',
        'description' => 'Remove a plugin registration record',
    ];

    public static function all(): array
    {
        return [
            self::VIEW,
            self::INSTALL,
            self::REVIEW,
            self::ENABLE,
            self::DISABLE,
            self::DELETE,
        ];
    }
}
