<?php

declare(strict_types=1);    

namespace App\Core\Scheduler\Settings;

use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\DTO\Setting;
use App\Core\Settings\DTO\SettingFormFieldType;
use App\Core\Settings\DTO\SettingType;

class SchedulerSettings implements SettingsRegistryContract
{
    public const GROUP = 'app';

    public static function definitions(): array
    {
        return [
            new Setting( 
                key: 'scheduler.claim_window_seconds',
                type: SettingType::INTEGER,
                formFieldType: SettingFormFieldType::NUMBER,
                value: (int) env('SCHEDULER_CLAIM_WINDOW_SECONDS', 300),
                display_name: 'Scheduler Claim Window (seconds)',
                description: 'Time window (seconds) for preventing duplicate scheduled task dispatch. Increase for unreliable cron environments (e.g., Bluehost). Default: 300 (5 min). For shared hosting: 900-1800 (15-30 min).',
                group: self::GROUP,
                order: 1,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: false,
            ),
        ];
    }
}