<?php

namespace App\Domains\Accounting\Permissions;

class AccountingCodePermissions
{
    public const VIEW = [
        'resource' => 'accounting-codes',
        'action' => 'view',
        'description' => 'View accounting codes',
    ];

    public const CREATE = [
        'resource' => 'accounting-codes',
        'action' => 'create',
        'description' => 'Create accounting codes',
    ];

    public const EDIT = [
        'resource' => 'accounting-codes',
        'action' => 'edit',
        'description' => 'Edit accounting codes',
    ];

    public const DELETE = [
        'resource' => 'accounting-codes',
        'action' => 'delete',
        'description' => 'Delete accounting codes',
    ];

    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
        ];
    }
}
