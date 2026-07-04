<?php

namespace App\PlugIns\Zoom\Settings;

use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\DTO\Setting;
use App\Core\Settings\DTO\SettingFormFieldType;
use App\Core\Settings\DTO\SettingType;

class ZoomSettings implements SettingsRegistryContract
{
    public const GROUP =  'zoom';

    public static function definitions(): array
    {
        return [
            new Setting(
                key: 'zoom.account_id',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('ZOOM_ACCOUNT_ID', ''),
                display_name: 'Zoom Account ID',
                description: 'Zoom Server-to-Server OAuth account identifier.',
                group: self::GROUP,
                options: null,
                order: 1,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false,
            ),
            new Setting(
                key: 'zoom.client_id',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('ZOOM_CLIENT_ID', ''),
                display_name: 'Zoom Client ID',
                description: 'Zoom OAuth client ID used for SMS API authentication.',
                group: self::GROUP,
                options: null,
                order: 2,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false,
            ),
            new Setting(
                key: 'zoom.client_secret',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::PASSWORD,
                value: env('ZOOM_CLIENT_SECRET', ''),
                display_name: 'Zoom Client Secret',
                description: 'Zoom OAuth client secret used for token acquisition.',
                group: self::GROUP,
                options: null,
                order: 3,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: true,
            ),
            new Setting(
                key: 'zoom.token_url',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('ZOOM_TOKEN_URL', 'https://zoom.us/oauth/token'),
                display_name: 'Zoom Token URL',
                description: 'OAuth token endpoint for Zoom Server-to-Server credentials.',
                group: self::GROUP,
                options: null,
                order: 4,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false,
            ),
            new Setting(
                key: 'zoom.from_number',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('ZOOM_SMS_FROM_NUMBER', ''),
                display_name: 'Zoom SMS From Number',
                description: 'E.164 sender phone number used for outbound SMS.',
                group: self::GROUP,
                options: null,
                order: 5,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false,
            ),
            new Setting(
                key: 'zoom.zoom_user_id',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('ZOOM_USER_ID', ''),
                display_name: 'Zoom User ID',
                description: 'Zoom user ID owning the sender number for consent lookups.',
                group: self::GROUP,
                options: null,
                order: 6,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false,
            ),
            new Setting(
                key: 'zoom.sms_campaign_id',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('ZOOM_SMS_CAMPAIGN_ID', ''),
                display_name: 'Zoom SMS Campaign ID',
                description: 'Campaign identifier used for consent status synchronization.',
                group: self::GROUP,
                options: null,
                order: 7,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false,
            ),
            new Setting(
                key: 'zoom.api_base_url',
                type: SettingType::STRING,
                formFieldType: SettingFormFieldType::TEXT,
                value: env('ZOOM_API_BASE_URL', 'https://api.zoom.us/v2'),
                display_name: 'Zoom API Base URL',
                description: 'Base API URL used for Zoom SMS and consent endpoints.',
                group: self::GROUP,
                options: null,
                order: 8,
                is_visible: true,
                is_public: false,
                is_required: false,
                encrypted: false,
            ),
        ];
    }
}