<?php

namespace App\Domains\Invoices\Permissions;

class InvoicePermissions
{
    public const VIEW = [
        'resource' => 'invoices',
        'action' => 'view',
        'description' => 'View invoices',
    ];

    public const CREATE = [
        'resource' => 'invoices',
        'action' => 'create',
        'description' => 'Create invoices',
    ];

    public const EDIT = [
        'resource' => 'invoices',
        'action' => 'edit',
        'description' => 'Edit invoices',
    ];

    public const DELETE = [
        'resource' => 'invoices',
        'action' => 'delete',
        'description' => 'Delete invoices',
    ];

    public const VERIFY = [
        'resource' => 'invoices',
        'action' => 'verify',
        'description' => 'Verify invoices',
    ];

    public const MARK_PAID = [
        'resource' => 'invoices',
        'action' => 'mark-paid',
        'description' => 'Mark invoices as paid',
    ];

    public const REJECT = [
        'resource' => 'invoices',
        'action' => 'reject',
        'description' => 'Reject invoices',
    ];

    /**
     * @return array<int, array<string, string>>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::DELETE,
            self::VERIFY,
            self::MARK_PAID,
            self::REJECT,
        ];
    }
}
