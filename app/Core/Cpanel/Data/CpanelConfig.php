<?php

namespace App\Core\Cpanel\Data;

use App\Core\Settings\Facades\Settings;

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

    public function __construct()
    {
        $this->url = Settings::get('cpanel.url', null)->toNullableString();
        $this->username = Settings::get('cpanel.username', null)->toNullableString();
        $this->apiToken = Settings::get('cpanel.api_token', null)->toNullableString();
        $this->domain = Settings::get('cpanel.domain', null)->toNullableString();
        $this->port = Settings::get('cpanel.port', 2083)->toInt();
        $this->webmailPort = Settings::get('cpanel.webmail_port', 2096)->toInt();
        $this->webmailUrl = Settings::get('cpanel.webmail_url', null)->toNullableString();
        $this->defaultEmailQuota = Settings::get('cpanel.default_email_quota', 250)->toInt();
        $this->autoCreateEmails = Settings::get('cpanel.auto_create_emails', false)->toBool();
        $this->autoDeleteEmails = Settings::get('cpanel.auto_delete_emails', true)->toBool();
        $this->syncUserPasswords = Settings::get('cpanel.sync_user_passwords', false)->toBool();
        $this->queueWriteOperations = Settings::get('cpanel.queue_write_operations', false)->toBool();
        $this->idempotencyTtlSeconds = Settings::get('cpanel.idempotency_ttl_seconds', 120)->toInt();
        $this->queueTries = Settings::get('cpanel.queue_tries', 3)->toInt();
        $this->queueBackoff = Settings::get('cpanel.queue_backoff', '10,30,60')->toString();
        $this->failureThreshold = Settings::get('cpanel.failure_threshold', 5)->toInt();
        $this->cooldownSeconds = Settings::get('cpanel.cooldown_seconds', 300)->toInt();
        $this->telemetryKeyPrefix = Settings::get('cpanel.telemetry_key_prefix', 'cpanel.telemetry')->toString();
        $this->verifySsl = Settings::get('cpanel.verify_ssl', true)->toBool();
        $this->timeout = Settings::get('cpanel.timeout', 30)->toInt();
        $this->connectTimeout = Settings::get('cpanel.connect_timeout', 10)->toInt();
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
}
