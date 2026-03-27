<?php

namespace App\Domains\Stock\Permissions;

class StockOrderTemplatePermissions
{
    public const VIEW_ANY = [
        'resource' => 'stock-order-templates',
        'action' => 'view-any',
        'description' => 'View all stock order templates',
    ];

    public const VIEW = [
        'resource' => 'stock-order-templates',
        'action' => 'view',
        'description' => 'View stock order templates',
    ];

    public const CREATE = [
        'resource' => 'stock-order-templates',
        'action' => 'create',
        'description' => 'Create stock order templates',
    ];

    public const UPDATE = [
        'resource' => 'stock-order-templates',
        'action' => 'update',
        'description' => 'Update stock order templates',
    ];

    public const DELETE = [
        'resource' => 'stock-order-templates',
        'action' => 'delete',
        'description' => 'Delete stock order templates',
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
        ];
    }
}
