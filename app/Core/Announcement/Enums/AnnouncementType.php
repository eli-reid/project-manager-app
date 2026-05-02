<?php

namespace App\Core\Announcement\Enums;

enum AnnouncementType: string
{
    case Info = 'info';
    case Success = 'success';
    case Warning = 'warning';
    case Error = 'error';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Information',
            self::Success => 'Success',
            self::Warning => 'Warning',
            self::Error => 'Error',
            self::General => 'General',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Info => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
            self::Success => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            self::Warning => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            self::Error => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            self::General => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(static function (self $type): array {
            return [
                'value' => $type->value,
                'label' => $type->label(),
            ];
        }, self::cases());
    }
}
