<?php

namespace App\Core\Cpanel\Services;

use App\Core\Cpanel\Data\CpanelConfig;
use App\Core\Cpanel\Exceptions\CpanelConfigurationException;
use App\Core\Cpanel\Exceptions\CpanelRequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CpanelService
{
    public function __construct(
        protected CpanelConfig $config
    ) {}

    public function isConfigured(): bool
    {
        return $this->config->isConfigured();
    }

    /**
     * @return array{success: bool, message: string, emails?: array<int, array<string, mixed>>, count?: int, data?: array<string, mixed>}
     */
    public function listEmailAccounts(?string $regex = null): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $regex = $regex ?? $this->config->defaultRegex();

        try {
            $result = $this->request('Email', 'list_pops_with_disk', 'get', ['regex' => $regex]);
            $emails = $this->normalizeEmailAccounts($result['data'] ?? []);

            return [
                'success' => true,
                'message' => 'Email accounts retrieved successfully.',
                'emails' => $emails,
                'count' => count($emails),
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to list email accounts.');
        }
    }

    /**
     * @return array{success: bool, message: string, email?: string, data?: array<string, mixed>}
     */
    public function createEmailAccount(string $emailUsername, string $password, ?int $quota = null): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $emailUsername = trim($emailUsername);
        if ($emailUsername === '') {
            return [
                'success' => false,
                'message' => 'Email username is required.',
            ];
        }

        try {
            $this->request('Email', 'add_pop', 'post', [
                'email' => $emailUsername,
                'password' => $password,
                'domain' => $this->config->domain,
                'quota' => $quota ?? $this->config->defaultEmailQuota,
            ]);

            return [
                'success' => true,
                'message' => 'Email account created successfully.',
                'email' => $emailUsername.'@'.$this->config->domain,
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to create email account.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function deleteEmailAccount(string $email): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $localPart = $this->extractLocalPart($email);
        if ($localPart === '') {
            return [
                'success' => false,
                'message' => 'Valid email is required.',
            ];
        }

        try {
            $this->request('Email', 'delete_pop', 'post', [
                'email' => $localPart,
                'domain' => $this->config->domain,
            ]);

            return [
                'success' => true,
                'message' => 'Email account deleted successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to delete email account.');
        }
    }

    /**
     * @return array{success: bool, message: string, url?: string, data?: array<string, mixed>}
     */
    public function createWebmailSession(string $email): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $email = trim($email);
        if ($email === '') {
            return [
                'success' => false,
                'message' => 'Email is required.',
            ];
        }

        try {
            $result = $this->request('Session', 'create_webmail_session_for_self', 'post', ['user' => $email]);
            $url = (string) ($result['data']['url'] ?? '');

            if ($url === '') {
                $url = $this->webmailRedirectUrl($email);
            }

            return [
                'success' => true,
                'message' => 'Webmail session created successfully.',
                'url' => $url,
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to create webmail session.');
        }
    }

    public function webmailRedirectUrl(string $email): string
    {
        return $this->config->webmailBaseUrl().'/?user='.urlencode($email);
    }

    public function configuration(): CpanelConfig
    {
        return $this->config;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function request(string $module, string $function, string $method = 'get', array $params = []): array
    {
        $this->ensureConfigured();

        try {
            $pending = Http::timeout($this->config->timeout)
                ->withOptions([
                    'verify' => $this->config->verifySsl,
                    'connect_timeout' => $this->config->connectTimeout,
                ])
                ->withHeaders([
                    'Authorization' => $this->config->authorizationHeader(),
                    'Accept' => 'application/json',
                ]);

            $url = $this->config->buildApiUrl($module, $function);

            $response = $method === 'post'
                ? $pending->asForm()->post($url, $params)
                : $pending->get($url, $params);

            $payload = $response->json();
            if (! is_array($payload)) {
                $payload = [];
            }

            if (! $response->successful()) {
                throw new CpanelRequestException(
                    message: 'cPanel request failed with HTTP status '.$response->status().'.',
                    context: [
                        'http_status' => $response->status(),
                        'module' => $module,
                        'function' => $function,
                        'payload' => $payload,
                    ]
                );
            }

            $status = (int) ($payload['status'] ?? 0);
            if ($status !== 1) {
                $errors = $payload['errors'] ?? [];
                $message = is_array($errors) && $errors !== []
                    ? implode(', ', array_map('strval', $errors))
                    : 'cPanel request was not successful.';

                throw new CpanelRequestException(
                    message: $message,
                    context: [
                        'module' => $module,
                        'function' => $function,
                        'payload' => $payload,
                    ]
                );
            }

            return $payload;
        } catch (ConnectionException $exception) {
            throw new CpanelRequestException(
                message: 'Unable to connect to cPanel API.',
                context: [
                    'module' => $module,
                    'function' => $function,
                    'error' => $exception->getMessage(),
                ],
                previous: $exception
            );
        }
    }

    protected function ensureConfigured(): void
    {
        if ($this->isConfigured()) {
            return;
        }

        throw new CpanelConfigurationException('cPanel configuration is incomplete. Please provide url, username, api_token, and domain.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $accounts
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeEmailAccounts(array $accounts): array
    {
        return collect($accounts)
            ->map(function (array $account): array {
                $emailValue = (string) ($account['email'] ?? '');
                $email = str_contains($emailValue, '@')
                    ? $emailValue
                    : $emailValue.'@'.$this->config->domain;

                return [
                    'email' => $email,
                    'username' => $emailValue,
                    'domain' => (string) ($account['domain'] ?? $this->config->domain),
                    'quota' => $this->toInteger($account['txtdiskquota'] ?? $account['diskquota'] ?? $account['quota'] ?? null),
                    'usage' => $this->toInteger($account['diskused'] ?? $account['txtdiskused'] ?? $account['humandiskused'] ?? null),
                    'suspended' => (bool) ($account['suspended_login'] ?? $account['suspended'] ?? false),
                ];
            })
            ->values()
            ->all();
    }

    protected function extractLocalPart(string $email): string
    {
        $normalized = trim($email);
        if ($normalized === '') {
            return '';
        }

        if (! str_contains($normalized, '@')) {
            return $normalized;
        }

        [$localPart] = explode('@', $normalized, 2);

        return trim($localPart);
    }

    protected function toInteger(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            if ($value === '' || strcasecmp($value, 'unlimited') === 0) {
                return null;
            }

            if (preg_match('/-?\\d+/', $value, $matches) === 1) {
                return (int) $matches[0];
            }
        }

        if (is_float($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @return array{success: false, message: string}
     */
    protected function configurationErrorResponse(): array
    {
        return [
            'success' => false,
            'message' => 'cPanel configuration is incomplete.',
        ];
    }

    /**
     * @return array{success: false, message: string, data: array<string, mixed>}
     */
    protected function requestErrorResponse(Throwable $exception, string $fallbackMessage): array
    {
        if ($exception instanceof CpanelRequestException) {
            Log::warning('cPanel API request failed.', [
                'message' => $exception->getMessage(),
                'context' => $exception->context,
            ]);

            return [
                'success' => false,
                'message' => $exception->getMessage() !== '' ? $exception->getMessage() : $fallbackMessage,
                'data' => $exception->context,
            ];
        }

        Log::warning('Unexpected cPanel service error.', ['error' => $exception->getMessage()]);

        return [
            'success' => false,
            'message' => $fallbackMessage,
            'data' => ['error' => $exception->getMessage()],
        ];
    }
}
