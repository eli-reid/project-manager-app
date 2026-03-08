<?php

namespace App\Core\Cpanel\Data;

class CpanelConfig
{
    public function __construct(
        public readonly ?string $url,
        public readonly ?string $username,
        public readonly ?string $apiToken,
        public readonly ?string $domain,
        public readonly int $port = 2083,
        public readonly int $webmailPort = 2096,
        public readonly ?string $webmailUrl = null,
        public readonly int $defaultEmailQuota = 250,
        public readonly bool $autoCreateEmails = false,
        public readonly bool $autoDeleteEmails = true,
        public readonly bool $syncUserPasswords = false,
        public readonly bool $verifySsl = true,
        public readonly int $timeout = 30,
        public readonly int $connectTimeout = 10,
    ) {}

    /**
     * @param  array<string, mixed>|null  $config
     */
    public static function fromServicesConfig(?array $config): self
    {
        $config = $config ?? [];

        return new self(
            url: self::normalizeNullableString($config['url'] ?? null),
            username: self::normalizeNullableString($config['username'] ?? null),
            apiToken: self::normalizeNullableString($config['api_token'] ?? null),
            domain: self::normalizeNullableString($config['domain'] ?? null),
            port: (int) ($config['port'] ?? 2083),
            webmailPort: (int) ($config['webmail_port'] ?? 2096),
            webmailUrl: self::normalizeNullableString($config['webmail_url'] ?? null),
            defaultEmailQuota: (int) ($config['default_email_quota'] ?? 250),
            autoCreateEmails: (bool) ($config['auto_create_emails'] ?? false),
            autoDeleteEmails: (bool) ($config['auto_delete_emails'] ?? true),
            syncUserPasswords: (bool) ($config['sync_user_passwords'] ?? false),
            verifySsl: (bool) ($config['verify_ssl'] ?? true),
            timeout: (int) ($config['timeout'] ?? 30),
            connectTimeout: (int) ($config['connect_timeout'] ?? 10),
        );
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
}
