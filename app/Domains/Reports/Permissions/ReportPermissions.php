<?php

namespace App\Domains\Reports\Permissions;

class ReportPermissions
{
    public const FINANCIAL_VIEW = [
        'resource' => 'financial-reports',
        'action' => 'view',
        'description' => 'View financial reports',
    ];

    public const FINANCIAL_EXPORT = [
        'resource' => 'financial-reports',
        'action' => 'export',
        'description' => 'Export financial reports',
    ];

    public const OPERATIONAL_VIEW = [
        'resource' => 'operational-reports',
        'action' => 'view',
        'description' => 'View operational reports',
    ];

    public const OPERATIONAL_CREATE_TEMPLATE = [
        'resource' => 'operational-reports',
        'action' => 'create-template',
        'description' => 'Create operational report templates',
    ];

    public const OPERATIONAL_SCHEDULE = [
        'resource' => 'operational-reports',
        'action' => 'schedule',
        'description' => 'Schedule operational reports',
    ];

    public const OPERATIONAL_EXPORT = [
        'resource' => 'operational-reports',
        'action' => 'export',
        'description' => 'Export operational reports',
    ];

    public static function all(): array
    {
        return [
            self::FINANCIAL_VIEW,
            self::FINANCIAL_EXPORT,
            self::OPERATIONAL_VIEW,
            self::OPERATIONAL_CREATE_TEMPLATE,
            self::OPERATIONAL_SCHEDULE,
            self::OPERATIONAL_EXPORT,
        ];
    }
}
