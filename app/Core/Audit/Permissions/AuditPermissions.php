<?php

namespace App\Core\Audit\Permissions;

class AuditPermissions
{
    public const VIEW_AUDIT_LOGS = [
        'resource' => 'audit-logs',
        'action' => 'view',
        'description' => 'View audit log records',
    ];

    public const EXPORT_AUDIT_LOGS = [
        'resource' => 'audit-logs',
        'action' => 'export',
        'description' => 'Export audit log records',
    ];

    public static function all(): array
    {
        return [
            self::VIEW_AUDIT_LOGS,
            self::EXPORT_AUDIT_LOGS,
        ];
    }
}
