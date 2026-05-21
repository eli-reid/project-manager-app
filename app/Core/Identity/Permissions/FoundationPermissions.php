<?php

namespace App\Core\Identity\Permissions;

class FoundationPermissions
{
    public const NAVIGATION_VIEW_ADMIN = [
        'resource' => 'navigation',
        'action' => 'view-admin',
        'description' => 'View administration navigation section',
    ];

    public const PROJECT_ACCESS_VIEW = [
        'resource' => 'project-access',
        'action' => 'view',
        'description' => 'View project access assignments',
    ];

    public const PROJECT_ACCESS_GRANT = [
        'resource' => 'project-access',
        'action' => 'grant',
        'description' => 'Grant project access to users and groups',
    ];

    public const PROJECT_ACCESS_REVOKE = [
        'resource' => 'project-access',
        'action' => 'revoke',
        'description' => 'Revoke project access from users and groups',
    ];

    public const PROJECT_ACCESS_MANAGE = [
        'resource' => 'project-access',
        'action' => 'manage',
        'description' => 'Manage project access policies',
    ];

    public const RATE_MANAGEMENT_VIEW = [
        'resource' => 'rate-management',
        'action' => 'view',
        'description' => 'View pay and burden rates',
    ];

    public const RATE_MANAGEMENT_EDIT = [
        'resource' => 'rate-management',
        'action' => 'edit',
        'description' => 'Create and edit pay and burden rates',
    ];

    public const RATE_MANAGEMENT_COMPONENTS = [
        'resource' => 'rate-management',
        'action' => 'manage-components',
        'description' => 'Manage burden rate components',
    ];

    public const DOCUMENT_SHARING_VIEW = [
        'resource' => 'document-sharing',
        'action' => 'view',
        'description' => 'View document share links',
    ];

    public const DOCUMENT_SHARING_CREATE = [
        'resource' => 'document-sharing',
        'action' => 'create',
        'description' => 'Create document share links',
    ];

    public const DOCUMENT_SHARING_REVOKE = [
        'resource' => 'document-sharing',
        'action' => 'revoke',
        'description' => 'Revoke document share links',
    ];

    public const VENDORS_VIEW = [
        'resource' => 'vendors',
        'action' => 'view',
        'description' => 'View vendors',
    ];

    public const VENDORS_CREATE = [
        'resource' => 'vendors',
        'action' => 'create',
        'description' => 'Create vendors',
    ];

    public const VENDORS_EDIT = [
        'resource' => 'vendors',
        'action' => 'edit',
        'description' => 'Edit vendors',
    ];

    public const VENDORS_DEACTIVATE = [
        'resource' => 'vendors',
        'action' => 'deactivate',
        'description' => 'Deactivate vendors',
    ];

    public static function all(): array
    {
        return [
            self::NAVIGATION_VIEW_ADMIN,
            self::PROJECT_ACCESS_VIEW,
            self::PROJECT_ACCESS_GRANT,
            self::PROJECT_ACCESS_REVOKE,
            self::PROJECT_ACCESS_MANAGE,
            self::RATE_MANAGEMENT_VIEW,
            self::RATE_MANAGEMENT_EDIT,
            self::RATE_MANAGEMENT_COMPONENTS,
            self::DOCUMENT_SHARING_VIEW,
            self::DOCUMENT_SHARING_CREATE,
            self::DOCUMENT_SHARING_REVOKE,
            self::VENDORS_VIEW,
            self::VENDORS_CREATE,
            self::VENDORS_EDIT,
            self::VENDORS_DEACTIVATE,
        ];
    }
}
