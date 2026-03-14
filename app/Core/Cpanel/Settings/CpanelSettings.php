<?php

namespace App\Core\Cpanel\Settings;

use App\Core\Settings\Contracts\DomainSettingsProvider;

class CpanelSettings implements DomainSettingsProvider
{
    public static function settings(): array
    {
        return [
            self::textSetting('cpanel.url', 'URL', 'cPanel server URL (including protocol).', 1),
            self::textSetting('cpanel.username', 'Username', 'cPanel username used for API auth.', 2),
            self::tokenSetting(),
            self::textSetting('cpanel.domain', 'Domain', 'Default domain used for mailbox operations.', 4),
            self::numberSetting('cpanel.port', 'Port', 'cPanel API port.', 2083, 5),
            self::numberSetting('cpanel.webmail_port', 'Webmail Port', 'Webmail port for redirect fallback.', 2096, 6),
            self::textSetting('cpanel.webmail_url', 'Webmail URL', 'Optional explicit webmail URL.', 7, 'url'),
            self::numberSetting('cpanel.default_email_quota', 'Default Email Quota (MB)', 'Default mailbox quota in MB.', 250, 8),
            self::booleanSetting('cpanel.auto_create_emails', 'Auto Create Emails', 'Automatically create mailbox accounts for eligible users.', false, 9),
            self::booleanSetting('cpanel.auto_delete_emails', 'Auto Delete Emails', 'Delete mailbox accounts when users are deleted.', true, 10),
            self::booleanSetting('cpanel.sync_user_passwords', 'Sync User Passwords', 'Sync application password changes to mailbox accounts.', false, 11),
            self::booleanSetting('cpanel.queue_write_operations', 'Queue Write Operations', 'Queue write-side cPanel operations for resilience.', false, 12),
            self::numberSetting('cpanel.idempotency_ttl_seconds', 'Idempotency TTL (Seconds)', 'Deduplicate repeated write operations within this window.', 120, 13),
            self::numberSetting('cpanel.queue_tries', 'Queue Retries', 'Maximum number of attempts for queued write operations.', 3, 14),
            self::textSetting('cpanel.queue_backoff', 'Queue Backoff', 'Comma-separated backoff seconds for queued retries.', 15),
            self::numberSetting('cpanel.failure_threshold', 'Failure Threshold', 'Consecutive failures before cPanel cooldown activates.', 5, 16),
            self::numberSetting('cpanel.cooldown_seconds', 'Cooldown Seconds', 'Skip write operations while cooldown is active.', 300, 17),
            self::textSetting('cpanel.telemetry_key_prefix', 'Telemetry Key Prefix', 'Cache key prefix for cPanel success/failure counters.', 18),
            self::booleanSetting('cpanel.verify_ssl', 'Verify SSL', 'Require SSL certificate verification for cPanel API requests.', true, 19),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function textSetting(string $key, string $label, string $description, int $order, string $type = 'text'): array
    {
        return [
            'key' => $key,
            'value' => self::value($key),
            'display_name' => 'cPanel '.$label,
            'description' => $description,
            'type' => $type,
            'group' => 'services',
            'order' => $order,
            'is_visible' => true,
            'is_public' => false,
            'is_required' => false,
            'encrypted' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function numberSetting(string $key, string $label, string $description, int $defaultValue, int $order): array
    {
        return [
            'key' => $key,
            'value' => self::value($key, (string) $defaultValue),
            'display_name' => 'cPanel '.$label,
            'description' => $description,
            'type' => 'number',
            'group' => 'services',
            'order' => $order,
            'is_visible' => true,
            'is_public' => false,
            'is_required' => false,
            'encrypted' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function booleanSetting(string $key, string $label, string $description, bool $defaultValue, int $order): array
    {
        return [
            'key' => $key,
            'value' => self::value($key, $defaultValue ? 'true' : 'false'),
            'display_name' => 'cPanel '.$label,
            'description' => $description,
            'type' => 'select',
            'group' => 'services',
            'options' => [
                'true' => 'Yes',
                'false' => 'No',
            ],
            'order' => $order,
            'is_visible' => true,
            'is_public' => false,
            'is_required' => false,
            'encrypted' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function tokenSetting(): array
    {
        return [
            'key' => 'cpanel.api_token',
            'value' => self::value('cpanel.api_token'),
            'display_name' => 'cPanel API Token',
            'description' => 'API token used for cPanel requests.',
            'type' => 'password',
            'group' => 'services',
            'order' => 3,
            'is_visible' => true,
            'is_public' => false,
            'is_required' => false,
            'encrypted' => true,
        ];
    }

    private static function value(string $key, string $default = ''): string
    {
        $serviceKey = (string) str($key)->after('cpanel.');

        return (string) config('services.cpanel.'.$serviceKey, $default);
    }
}
