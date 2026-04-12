<?php

namespace App\Domains\Payroll\Permissions;

class PayrollPermissions
{
    public const REPORTS_VIEW = [
        'resource' => 'payroll-reports',
        'action' => 'view',
        'description' => 'View payroll compliance and analytics reports',
    ];

    public const REPORTS_EXPORT = [
        'resource' => 'payroll-reports',
        'action' => 'export',
        'description' => 'Export payroll reports',
    ];

    public const REPORTS_GENERATE = [
        'resource' => 'payroll-reports',
        'action' => 'generate',
        'description' => 'Generate payroll report outputs',
    ];

    public const REPORTS_CERTIFY = [
        'resource' => 'payroll-reports',
        'action' => 'certify',
        'description' => 'Certify payroll reports for submission workflows',
    ];

    public const REPORTS_REMIT = [
        'resource' => 'payroll-reports',
        'action' => 'remit',
        'description' => 'Generate and manage union remittance outputs',
    ];

    public const REPORTS_MANAGE = [
        'resource' => 'payroll-reports',
        'action' => 'manage',
        'description' => 'Manage payroll report configuration and templates',
    ];

    public static function all(): array
    {
        return [
            self::REPORTS_VIEW,
            self::REPORTS_EXPORT,
            self::REPORTS_GENERATE,
            self::REPORTS_CERTIFY,
            self::REPORTS_REMIT,
            self::REPORTS_MANAGE,
        ];
    }
}
