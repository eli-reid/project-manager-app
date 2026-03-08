<?php

namespace App\Core\Settings\Permissions;

class SettingsPermissions
{
    public const VIEW_SETTINGS = [
        'resource' => 'settings',
        'action' => 'view',
        'description' => 'View application settings',
    ];

    public const EDIT_SETTINGS = [
        'resource' => 'settings',
        'action' => 'edit',
        'description' => 'Edit application settings',
    ];

    public const IMPORT_SETTINGS = [
        'resource' => 'settings',
        'action' => 'import',
        'description' => 'Import application settings',
    ];

    public static function all(): array
    {
        return [
            self::VIEW_SETTINGS,
            self::EDIT_SETTINGS,
            self::IMPORT_SETTINGS,
        ];
    }
}
