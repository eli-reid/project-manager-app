<?php

namespace App\Core\Dashboard\Enums;

enum DashboardSection: string
{
    case Primary = 'primary';
    case Personal = 'personal';
    case Operations = 'operations';
    case Alerts = 'alerts';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Primary => __('dashboard.sections.primary'),
            self::Personal => __('dashboard.sections.personal'),
            self::Operations => __('dashboard.sections.operations'),
            self::Alerts => __('dashboard.sections.alerts'),
            self::Admin => __('dashboard.sections.admin'),
        };
    }
}
