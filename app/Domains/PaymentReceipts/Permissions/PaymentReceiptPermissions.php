<?php

namespace App\Domains\PaymentReceipts\Permissions;

class PaymentReceiptPermissions
{
    public const VIEW = [
        'resource' => 'payment-receipts',
        'action' => 'view',
        'description' => 'View project payment receipts',
    ];

    public const CREATE = [
        'resource' => 'payment-receipts',
        'action' => 'create',
        'description' => 'Create project payment receipts',
    ];

    public const DELETE = [
        'resource' => 'payment-receipts',
        'action' => 'delete',
        'description' => 'Delete project payment receipts',
    ];

    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::DELETE,
        ];
    }
}
