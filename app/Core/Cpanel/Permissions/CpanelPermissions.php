<?php

namespace App\Core\Cpanel\Permissions;

class CpanelPermissions
{
    public const MANAGE_EMAIL_ACCOUNTS = [
        'resource' => 'cpanel',
        'action' => 'manage-email-accounts',
        'description' => 'Manage cPanel mailbox accounts and webmail access actions',
    ];

    public static function all(): array
    {
        return [
            self::MANAGE_EMAIL_ACCOUNTS,
        ];
    }
}
