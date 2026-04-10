<?php

namespace App\Domains\Payroll\Permissions;

class PayrollPermissions
{
    public const VIEW = [
        'resource' => 'payroll',
        'action' => 'view',
        'description' => 'View payroll data for assigned users',
    ];

    public const VIEW_ALL = [
        'resource' => 'payroll',
        'action' => 'view-all',
        'description' => 'View all payroll data',
    ];

    public const PROCESS = [
        'resource' => 'payroll',
        'action' => 'process',
        'description' => 'Process payroll runs',
    ];

    public const APPROVE = [
        'resource' => 'payroll',
        'action' => 'approve',
        'description' => 'Approve payroll runs',
    ];

    public const EXPORT = [
        'resource' => 'payroll',
        'action' => 'export',
        'description' => 'Export payroll data',
    ];

    public const MANAGE = [
        'resource' => 'payroll',
        'action' => 'manage',
        'description' => 'Manage payroll settings and rates',
    ];

    public const FINALIZE = [
        'resource' => 'payroll',
        'action' => 'finalize',
        'description' => 'Finalize payroll periods',
    ];

    public const CORRECT = [
        'resource' => 'payroll',
        'action' => 'correct',
        'description' => 'Create and manage payroll corrections',
    ];

    public static function all(): array
    {
        return [
            self::VIEW,
            self::VIEW_ALL,
            self::PROCESS,
            self::APPROVE,
            self::EXPORT,
            self::MANAGE,
            self::FINALIZE,
            self::CORRECT,
        ];
    }
}
