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
        $settings = Settings::getMultiple([
            'cpanel.url',
            'cpanel.username',
            'cpanel.api_token',
            'cpanel.domain',
            'cpanel.port',
            'cpanel.webmail_port',
            'cpanel.webmail_url',
            'cpanel.default_email_quota',
            'cpanel.auto_create_emails',
            'cpanel.auto_delete_emails',
            'cpanel.sync_user_passwords',
            'cpanel.queue_write_operations',
            'cpanel.idempotency_ttl_seconds',
            'cpanel.queue_tries',
            'cpanel.queue_backoff',
            'cpanel.failure_threshold',
            'cpanel.cooldown_seconds',
            'cpanel.telemetry_key_prefix',
            'cpanel.verify_ssl',
            'cpanel.timeout',
            'cpanel.connect_timeout',
        ]);

        $value = static fn (string $key) => $settings[$key] ?? Settings::get($key, null);

        $this->url = $value('cpanel.url')->toNullableString();
        $this->username = $value('cpanel.username')->toNullableString();
        $this->apiToken = $value('cpanel.api_token')->toNullableString();
        $this->domain = $value('cpanel.domain')->toNullableString();
        $this->port = $value('cpanel.port')->toInt(2083);
        $this->webmailPort = $value('cpanel.webmail_port')->toInt(2096);
        $this->webmailUrl = $value('cpanel.webmail_url')->toNullableString();
        $this->defaultEmailQuota = $value('cpanel.default_email_quota')->toInt(250);
        $this->autoCreateEmails = $value('cpanel.auto_create_emails')->toBool(false);
        $this->autoDeleteEmails = $value('cpanel.auto_delete_emails')->toBool(true);
        $this->syncUserPasswords = $value('cpanel.sync_user_passwords')->toBool(false);
        $this->queueWriteOperations = $value('cpanel.queue_write_operations')->toBool(false);
        $this->idempotencyTtlSeconds = $value('cpanel.idempotency_ttl_seconds')->toInt(120);
        $this->queueTries = $value('cpanel.queue_tries')->toInt(3);
        $this->queueBackoff = $value('cpanel.queue_backoff')->toString('10,30,60');
        $this->failureThreshold = $value('cpanel.failure_threshold')->toInt(5);
        $this->cooldownSeconds = $value('cpanel.cooldown_seconds')->toInt(300);
        $this->telemetryKeyPrefix = $value('cpanel.telemetry_key_prefix')->toString('cpanel.telemetry');
        $this->verifySsl = $value('cpanel.verify_ssl')->toBool(true);
        $this->timeout = $value('cpanel.timeout')->toInt(30);
        $this->connectTimeout = $value('cpanel.connect_timeout')->toInt(10);
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

        if (parse_url($baseUrl, PHP_URL_PORT) === null) {
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
