<?php

namespace App\Domains\Stock\Permissions;

class StockOrderPermissions
{
    public const VIEW_ANY = [
        'resource' => 'stock-orders',
        'action' => 'view-any',
        'description' => 'View all stock orders',
    ];

    public const VIEW = [
        'resource' => 'stock-orders',
        'action' => 'view',
        'description' => 'View stock orders',
    ];

    public const CREATE = [
        'resource' => 'stock-orders',
        'action' => 'create',
        'description' => 'Create stock orders',
    ];

    public const UPDATE = [
        'resource' => 'stock-orders',
        'action' => 'update',
        'description' => 'Update stock orders',
    ];

    public const DELETE = [
        'resource' => 'stock-orders',
        'action' => 'delete',
        'description' => 'Delete stock orders',
    ];

    public const PROCESS = [
        'resource' => 'stock-orders',
        'action' => 'process',
        'description' => 'Process stock orders',
    ];

    /**
     * @return array<int, array<string, string>>
     */
    public static function all(): array
    {
        return [
            self::VIEW_ANY,
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::PROCESS,
        ];
    }
}
