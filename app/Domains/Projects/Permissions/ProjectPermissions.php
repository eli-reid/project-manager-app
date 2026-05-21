<?php

namespace App\Domains\Projects\Permissions;

class ProjectPermissions
{
    public const VIEW = [
        'resource' => 'projects',
        'action' => 'view',
        'description' => 'View projects',
    ];

    public const VIEW_ALL = [
        'resource' => 'projects',
        'action' => 'view-all',
        'description' => 'View all projects on project index',
    ];

    public const CREATE = [
        'resource' => 'projects',
        'action' => 'create',
        'description' => 'Create projects',
    ];

    public const EDIT = [
        'resource' => 'projects',
        'action' => 'edit',
        'description' => 'Edit projects',
    ];

    public const DELETE = [
        'resource' => 'projects',
        'action' => 'delete',
        'description' => 'Delete projects',
    ];

    public const VIEW_FINANCIALS = [
        'resource' => 'projects',
        'action' => 'view-financials',
        'description' => 'View project financials (budget vs invoiced)',
    ];

    public static function all(): array
    {
        return [
            self::VIEW,
            self::VIEW_ALL,
            self::CREATE,
            self::EDIT,
            self::DELETE,
            self::VIEW_FINANCIALS,
        ];
    }
}
