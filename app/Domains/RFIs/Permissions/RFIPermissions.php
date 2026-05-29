<?php

namespace App\Domains\RFIs\Permissions;

class RFIPermissions
{
    public const VIEW_ANY = [
        'resource' => 'rfis',
        'action' => 'view-any',
        'description' => 'View all RFIs',
    ];

    public const VIEW = [
        'resource' => 'rfis',
        'action' => 'view',
        'description' => 'View RFIs',
    ];

    public const CREATE = [
        'resource' => 'rfis',
        'action' => 'create',
        'description' => 'Create RFIs',
    ];

    public const UPDATE = [
        'resource' => 'rfis',
        'action' => 'update',
        'description' => 'Update RFIs',
    ];

    public const ANSWER = [
        'resource' => 'rfis',
        'action' => 'answer',
        'description' => 'Answer RFIs',
    ];

    public const CLOSE = [
        'resource' => 'rfis',
        'action' => 'close',
        'description' => 'Close RFIs',
    ];

    public static function all(): array
    {
        return [
            self::VIEW_ANY,
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::ANSWER,
            self::CLOSE,
        ];
    }
}
