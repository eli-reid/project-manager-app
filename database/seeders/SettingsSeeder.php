<?php

namespace Database\Seeders;

use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Database\Seeder;

/**
 * SettingsSeeder
 * 
 * Seeds default application settings into the settings SQLite database.
 * Run with: php artisan db:seed --class=SettingsSeeder
 */
class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure database is initialized
        $model = new SettingsSqlite();
        $model->ensureSettingsDatabase();

        // Seed application settings
        $this->seedApplicationSettings();
        $this->seedSystemSettings();
        $this->seedFeatureSettings();
    }

    /**
     * Seed application settings
     */
    protected function seedApplicationSettings(): void
    {
        $settings = [
            [
                'key' => 'app.timezone',
                'value' => config('app.timezone', 'UTC'),
                'display_name' => 'Timezone',
                'description' => 'Application timezone for date/time operations',
                'type' => 'select',
                'group' => 'app',
                'options' => $this->getTimezoneOptions(),
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.name',
                'value' => config('app.name', 'Project Manager'),
                'display_name' => 'Application Name',
                'description' => 'The name of your application',
                'type' => 'text',
                'group' => 'app',
                'order' => 2,
                'is_visible' => true,
                'is_public' => true,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.url',
                'value' => config('app.url', 'http://localhost'),
                'display_name' => 'Application URL',
                'description' => 'The URL of your application',
                'type' => 'url',
                'group' => 'app',
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
            [
                'key' => 'app.debug',
                'value' => (string) config('app.debug', false),
                'display_name' => 'Debug Mode',
                'description' => 'Enable debug mode for development',
                'type' => 'select',
                'group' => 'app',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => true,
                'encrypted' => false,
            ],
        ];

        foreach ($settings as $setting) {
            SettingsSqlite::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Seed system settings
     */
    protected function seedSystemSettings(): void
    {
        $settings = [
            [
                'key' => 'system.cache_enabled',
                'value' => (string) config('settings-db.cache.enabled', true),
                'display_name' => 'Enable Settings Cache',
                'description' => 'Cache settings for improved performance',
                'type' => 'select',
                'group' => 'system',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'system.cache_ttl',
                'value' => (string) config('settings-db.cache.ttl', 3600),
                'display_name' => 'Cache TTL (seconds)',
                'description' => 'How long to cache settings (in seconds)',
                'type' => 'integer',
                'group' => 'system',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'system.log_changes',
                'value' => (string) (env('SETTINGS_LOG_CHANGES', false) ? 'true' : 'false'),
                'display_name' => 'Log Settings Changes',
                'description' => 'Log all settings changes for audit trail',
                'type' => 'select',
                'group' => 'system',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];

        foreach ($settings as $setting) {
            SettingsSqlite::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Seed feature settings
     */
    protected function seedFeatureSettings(): void
    {
        $settings = [
            [
                'key' => 'features.maintenance_mode',
                'value' => 'false',
                'display_name' => 'Maintenance Mode',
                'description' => 'Enable maintenance mode to take application offline',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.new_user_registration',
                'value' => 'true',
                'display_name' => 'Allow New User Registration',
                'description' => 'Allow new users to register accounts',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'features.email_verification',
                'value' => 'true',
                'display_name' => 'Require Email Verification',
                'description' => 'Require users to verify their email before accessing the app',
                'type' => 'select',
                'group' => 'features',
                'options' => ['true' => 'Enabled', 'false' => 'Disabled'],
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];

        foreach ($settings as $setting) {
            SettingsSqlite::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Get array of timezone options for select field
     */
    protected function getTimezoneOptions(): string
    {
        $timezones = [
            'UTC' => 'UTC (Coordinated Universal Time)',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'America/Anchorage' => 'Alaska Time',
            'America/Adak' => 'Hawaii-Aleutian Time',
            'Europe/London' => 'Greenwich Mean Time',
            'Europe/Paris' => 'Central European Time',
            'Europe/Moscow' => 'Moscow Standard Time',
            'Asia/Dubai' => 'Gulf Standard Time',
            'Asia/Kolkata' => 'Indian Standard Time',
            'Asia/Bangkok' => 'Indochina Time',
            'Asia/Shanghai' => 'China Standard Time',
            'Asia/Seoul' => 'Korea Standard Time',
            'Asia/Tokyo' => 'Japan Standard Time',
            'Australia/Sydney' => 'Australian Eastern Time',
            'Pacific/Auckland' => 'New Zealand Standard Time',
        ];

        return json_encode($timezones);
    }
}
