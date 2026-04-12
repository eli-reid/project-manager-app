<?php

namespace App\Domains\Payroll\Enums;

enum PayRunStatus: string
{
    case Draft = 'draft';
    case Preview = 'preview';
    case Approved = 'approved';
    case Finalized = 'finalized';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Preview => 'Preview',
            self::Approved => 'Approved',
            self::Finalized => 'Finalized',
            self::Void => 'Void',
        };
    }

    public function isLocked(): bool
    {
        return match ($this) {
            self::Finalized, self::Void => true,
            default => false,
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => $next === self::Preview,
            self::Preview => $next === self::Approved || $next === self::Draft,
            self::Approved => $next === self::Finalized || $next === self::Preview,
            self::Finalized => $next === self::Void,
            self::Void => false,
        };
    }
}
