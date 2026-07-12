<?php

declare(strict_types=1);

namespace App\Core\Navigation\DTO;

enum NavSectionEnum: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case PROFILE = 'profile';
}
