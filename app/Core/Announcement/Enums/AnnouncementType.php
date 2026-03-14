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
