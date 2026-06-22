<?php

namespace App\Domains\Payroll\Enums;

enum OvertimeRule: string
{
    case WeeklyFlsa = 'weekly_flsa';
    case CaliforniaDaily = 'california_daily';

    public function label(): string
    {
        return match ($this) {
            self::WeeklyFlsa => 'Weekly (FLSA)',
            self::CaliforniaDaily => 'California Daily',
        };
    }
}
