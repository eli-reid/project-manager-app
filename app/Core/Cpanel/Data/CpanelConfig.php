<?php

namespace App\Core\Cpanel\Data;

use App\Core\Settings\Services\SettingsSqliteService;

class CpanelConfig
{
    public readonly ?string $url;

    public readonly ?string $username;

    public readonly ?string $apiToken;

    public readonly ?string $domain;

    public readonly int $port;

    public readonly int $webmailPort;

    public readonly ?string $webmailUrl;

    public readonly int $defaultEmailQuota;

    public readonly bool $autoCreateEmails;

    public readonly bool $autoDeleteEmails;

    public readonly bool $syncUserPasswords;

    public readonly bool $queueWriteOperations;

    public readonly int $idempotencyTtlSeconds;

    public readonly int $queueTries;

    public readonly string $queueBackoff;

    public readonly int $failureThreshold;

    public readonly int $cooldownSeconds;

    public readonly string $telemetryKeyPrefix;

    public readonly bool $verifySsl;

    public readonly int $timeout;

    public readonly int $connectTimeout;

    public function __construct(SettingsSqliteService $settingsService)
    {
        $this->url = self::normalizeNullableString($settingsService->get('cpanel.url', null));
        $this->username = self::normalizeNullableString($settingsService->get('cpanel.username', null));
        $this->apiToken = self::normalizeNullableString($settingsService->get('cpanel.api_token', null));
        $this->domain = self::normalizeNullableString($settingsService->get('cpanel.domain', null));
        $this->port = self::toInt($settingsService->get('cpanel.port', 2083), 2083);
        $this->webmailPort = self::toInt($settingsService->get('cpanel.webmail_port', 2096), 2096);
        $this->webmailUrl = self::normalizeNullableString($settingsService->get('cpanel.webmail_url', null));
        $this->defaultEmailQuota = self::toInt($settingsService->get('cpanel.default_email_quota', 250), 250);
        $this->autoCreateEmails = self::toBool($settingsService->get('cpanel.auto_create_emails', false), false);
        $this->autoDeleteEmails = self::toBool($settingsService->get('cpanel.auto_delete_emails', true), true);
        $this->syncUserPasswords = self::toBool($settingsService->get('cpanel.sync_user_passwords', false), false);
        $this->queueWriteOperations = self::toBool($settingsService->get('cpanel.queue_write_operations', false), false);
        $this->idempotencyTtlSeconds = self::toInt($settingsService->get('cpanel.idempotency_ttl_seconds', 120), 120);
        $this->queueTries = self::toInt($settingsService->get('cpanel.queue_tries', 3), 3);
        $this->queueBackoff = self::toString($settingsService->get('cpanel.queue_backoff', '10,30,60'), '10,30,60');
        $this->failureThreshold = self::toInt($settingsService->get('cpanel.failure_threshold', 5), 5);
        $this->cooldownSeconds = self::toInt($settingsService->get('cpanel.cooldown_seconds', 300), 300);
        $this->telemetryKeyPrefix = self::toString($settingsService->get('cpanel.telemetry_key_prefix', 'cpanel.telemetry'), 'cpanel.telemetry');
        $this->verifySsl = self::toBool($settingsService->get('cpanel.verify_ssl', true), true);
        $this->timeout = self::toInt($settingsService->get('cpanel.timeout', 30), 30);
        $this->connectTimeout = self::toInt($settingsService->get('cpanel.connect_timeout', 10), 10);
    }

    public function isConfigured(): bool
    {
        return $this->url !== null
            && $this->username !== null
            && $this->apiToken !== null
            && $this->domain !== null;
    }

    public function authorizationHeader(): string
    {
        return 'cpanel '.$this->username.':'.$this->apiToken;
    }

    public function buildApiUrl(string $module, string $function): string
    {
        $baseUrl = rtrim((string) $this->url, '/');

        if (! str_contains($baseUrl, ':'.$this->port)) {
            $baseUrl .= ':'.$this->port;
        }

        return $baseUrl.'/execute/'.$module.'/'.$function;
    }

    public function defaultRegex(): string
    {
        return '@'.$this->domain;
    }

    public function webmailBaseUrl(): string
    {
        if ($this->webmailUrl !== null) {
            return rtrim($this->webmailUrl, '/');
        }

        $baseUrl = rtrim((string) $this->url, '/');
        $baseUrl = preg_replace('/:\\d+$/', '', $baseUrl) ?: $baseUrl;

        return $baseUrl.':'.$this->webmailPort;
    }

    private static function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private static function toInt(mixed $value, int $default): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    private static function toString(mixed $value, string $default): string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return $default;
    }

    private static function toBool(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return match ($value) {
                1 => true,
                0 => false,
                default => $default,
            };
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return match ($normalized) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => $default,
            };
        }

        return $default;
    }
}
