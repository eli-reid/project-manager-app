<?php

declare(strict_types=1);

namespace App\Core\UI\Navigation\DTO;

enum NavSectionEnum: string
{
    case DASHBOARD = 'dashboard';
    case USER = 'user';
    case ADMIN = 'admin';
    case PROFILE = 'profile';
}
