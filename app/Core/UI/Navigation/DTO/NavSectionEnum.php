<?php

declare(strict_types=1);

namespace App\Core\UI\Navigation\DTO;

enum NavSectionEnum: string
{
    case DASHBOARD = 'dashboard';
    case USER = 'user';
    case ADMIN = 'admin';
    case PROFILE = 'profile';

    public function label(): string
    {
        return match ($this) {
            self::DASHBOARD => 'Dashboard',
            self::USER => 'My Workspace',
            self::ADMIN => 'Administration',
            self::PROFILE => 'Profile',
        };
    }
}
