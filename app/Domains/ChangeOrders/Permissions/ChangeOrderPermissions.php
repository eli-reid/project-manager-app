<?php

namespace App\Domains\ChangeOrders\Permissions;

class ChangeOrderPermissions
{
    public const VIEW = [
        'resource' => 'change-orders',
        'action' => 'view',
        'description' => 'View change orders',
    ];

    public const CREATE = [
        'resource' => 'change-orders',
        'action' => 'create',
        'description' => 'Create change orders',
    ];

    public const EDIT = [
        'resource' => 'change-orders',
        'action' => 'edit',
        'description' => 'Edit change orders',
    ];

    public const APPROVE = [
        'resource' => 'change-orders',
        'action' => 'approve',
        'description' => 'Approve or reject change orders',
    ];

    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::APPROVE,
        ];
    }
}
