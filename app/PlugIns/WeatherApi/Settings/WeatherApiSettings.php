<?php

declare(strict_types=1);

namespace App\PlugIns\WeatherApi\Settings;

use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\DTO\Setting;
use App\Core\Settings\DTO\SettingFormFieldType;
use App\Core\Settings\DTO\SettingType;

class WeatherApiSettings implements SettingsRegistryContract
{
    public const GROUP = 'weather';

    public static function definitions(): array
    {
        return [
            new Setting(
                key: 'weather.api_key',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: '',
                display_name: 'Weather API Key',
                description: 'API key for accessing the weather service.',
                group: self::GROUP,
                order: 1,
                is_visible: true,
                is_public: false,
                is_required: true,
                encrypted: true
            ),
        ];
    }
}
