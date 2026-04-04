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

        $emailUsername = $this->sanitizeLocalPart($emailUsername);
        if ($emailUsername === '') {
            return [
                'success' => false,
                'message' => 'Valid email username is required.',
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
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function updateEmailPassword(string $email, string $password): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $password = trim($password);
        if ($password === '') {
            return [
                'success' => false,
                'message' => 'Password is required.',
            ];
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        if ($localPart === '') {
            return [
                'success' => false,
                'message' => 'Valid email is required.',
            ];
        }

        try {
            $this->request('Email', 'passwd_pop', 'post', [
                'email' => $localPart,
                'password' => $password,
                'domain' => $domain,
            ]);

            return [
                'success' => true,
                'message' => 'Email password updated successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to update email password.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function suspendEmailAccount(string $email): array
    {
        return $this->updateMailboxLoginState($email, suspend: true);
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function unsuspendEmailAccount(string $email): array
    {
        return $this->updateMailboxLoginState($email, suspend: false);
    }

    /**
     * @return array{success: bool, message: string, forwarders?: array<int, array<string, mixed>>, count?: int, data?: array<string, mixed>}
     */
    public function listForwarders(?string $email = null): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $params = [
            'domain' => $this->config->domain,
        ];

        if ($email !== null && trim($email) !== '') {
            if (! filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Valid email is required.',
                ];
            }

            $params['regex'] = trim($email);
        }

        try {
            $result = $this->request('Email', 'list_forwarders', 'get', $params);
            $forwarders = $this->normalizeForwarders($result['data'] ?? []);

            return [
                'success' => true,
                'message' => 'Forwarders retrieved successfully.',
                'forwarders' => $forwarders,
                'count' => count($forwarders),
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to list forwarders.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function addForwarder(string $email, string $forwardTo): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        $forwardTo = trim($forwardTo);

        if ($localPart === '' || $forwardTo === '' || ! filter_var($forwardTo, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Valid email and forward destination are required.',
            ];
        }

        try {
            $this->request('Email', 'add_forwarder', 'post', [
                'email' => $localPart,
                'domain' => $domain,
                'fwdopt' => 'fwd',
                'fwdemail' => $forwardTo,
            ]);

            return [
                'success' => true,
                'message' => 'Forwarder created successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to create forwarder.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function deleteForwarder(string $email, string $forwardTo): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        $forwardTo = trim($forwardTo);

        if ($localPart === '' || $forwardTo === '' || ! filter_var($forwardTo, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Valid email and forward destination are required.',
            ];
        }

        try {
            $this->request('Email', 'delete_forwarder', 'post', [
                'email' => $localPart,
                'domain' => $domain,
                'fwdemail' => $forwardTo,
            ]);

            return [
                'success' => true,
                'message' => 'Forwarder deleted successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to delete forwarder.');
        }
    }

    /**
     * @return array{success: bool, message: string, autoresponders?: array<int, array<string, mixed>>, count?: int, data?: array<string, mixed>}
     */
    public function listAutoresponders(?string $email = null): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $params = [
            'domain' => $this->config->domain,
        ];

        if ($email !== null && trim($email) !== '') {
            if (! filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' => 'Valid email is required.',
                ];
            }

            $params['regex'] = trim($email);
        }

        try {
            $result = $this->request('Email', 'list_autoresponders', 'get', $params);

            $autoresponders = collect($result['data'] ?? [])
                ->map(function (array $record): array {
                    return [
                        'email' => (string) ($record['email'] ?? ''),
                        'subject' => (string) ($record['subject'] ?? ''),
                        'body' => (string) ($record['body'] ?? ''),
                    ];
                })
                ->values()
                ->all();

            return [
                'success' => true,
                'message' => 'Autoresponders retrieved successfully.',
                'autoresponders' => $autoresponders,
                'count' => count($autoresponders),
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to list autoresponders.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function createAutoresponder(string $email, string $subject, string $body): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        $subject = trim($subject);
        $body = trim($body);

        if ($localPart === '' || $subject === '' || $body === '') {
            return [
                'success' => false,
                'message' => 'Valid email, subject, and body are required.',
            ];
        }

        try {
            $this->request('Email', 'add_autoresponder', 'post', [
                'email' => $localPart,
                'domain' => $domain,
                'subject' => $subject,
                'body' => $body,
            ]);

            return [
                'success' => true,
                'message' => 'Autoresponder created successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to create autoresponder.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function deleteAutoresponder(string $email): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        if ($localPart === '') {
            return [
                'success' => false,
                'message' => 'Valid email is required.',
            ];
        }

        try {
            $this->request('Email', 'delete_autoresponder', 'post', [
                'email' => $localPart,
                'domain' => $domain,
            ]);

            return [
                'success' => true,
                'message' => 'Autoresponder deleted successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to delete autoresponder.');
        }
    }

    /**
     * @return array{success: bool, message: string, filters?: array<int, array<string, mixed>>, count?: int, data?: array<string, mixed>}
     */
    public function listEmailFilters(string $email): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        if ($localPart === '') {
            return [
                'success' => false,
                'message' => 'Valid email is required.',
            ];
        }

        try {
            $result = $this->request('Email', 'list_filters', 'get', [
                'account' => $localPart,
                'domain' => $domain,
            ]);

            $filters = collect($result['data'] ?? [])
                ->map(function (array $record): array {
                    return [
                        'name' => (string) ($record['filtername'] ?? $record['filter'] ?? ''),
                    ];
                })
                ->values()
                ->all();

            return [
                'success' => true,
                'message' => 'Email filters retrieved successfully.',
                'filters' => $filters,
                'count' => count($filters),
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to list email filters.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function createEmailFilter(string $email, string $filterName, string $fromContains, string $forwardTo): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        $filterName = trim($filterName);
        $fromContains = trim($fromContains);
        $forwardTo = trim($forwardTo);

        if ($localPart === '' || $filterName === '' || $fromContains === '' || ! filter_var($forwardTo, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Valid filter details are required.',
            ];
        }

        try {
            $this->request('Email', 'store_filter', 'post', [
                'account' => $localPart,
                'domain' => $domain,
                'filtername' => $filterName,
                'part1' => '$header_from:',
                'opt1' => 'contains',
                'val1' => $fromContains,
                'action1' => 'redirect',
                'dest1' => $forwardTo,
            ]);

            return [
                'success' => true,
                'message' => 'Email filter created successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to create email filter.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function deleteEmailFilter(string $email, string $filterName): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        $filterName = trim($filterName);

        if ($localPart === '' || $filterName === '') {
            return [
                'success' => false,
                'message' => 'Valid email and filter name are required.',
            ];
        }

        try {
            $this->request('Email', 'delete_filter', 'post', [
                'account' => $localPart,
                'domain' => $domain,
                'filtername' => $filterName,
            ]);

            return [
                'success' => true,
                'message' => 'Email filter deleted successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to delete email filter.');
        }
    }

    /**
     * @return array{success: bool, message: string, forwarders?: array<int, array<string, mixed>>, count?: int, data?: array<string, mixed>}
     */
    public function listDomainForwarders(): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        try {
            $result = $this->request('Email', 'list_domain_forwarders', 'get');

            $forwarders = collect($result['data'] ?? [])
                ->map(function (array $record): array {
                    return [
                        'domain' => (string) ($record['domain'] ?? ''),
                        'destination' => (string) ($record['destdomain'] ?? $record['destination'] ?? ''),
                    ];
                })
                ->values()
                ->all();

            return [
                'success' => true,
                'message' => 'Domain forwarders retrieved successfully.',
                'forwarders' => $forwarders,
                'count' => count($forwarders),
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to list domain forwarders.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function addDomainForwarder(string $domain, string $destinationDomain): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $domain = $this->sanitizeDomain($domain);
        $destinationDomain = $this->sanitizeDomain($destinationDomain);

        if ($domain === '' || $destinationDomain === '') {
            return [
                'success' => false,
                'message' => 'Valid source and destination domains are required.',
            ];
        }

        try {
            $this->request('Email', 'add_domain_forwarder', 'post', [
                'domain' => $domain,
                'destdomain' => $destinationDomain,
            ]);

            return [
                'success' => true,
                'message' => 'Domain forwarder created successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to create domain forwarder.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function deleteDomainForwarder(string $domain): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        $domain = $this->sanitizeDomain($domain);
        if ($domain === '') {
            return [
                'success' => false,
                'message' => 'Valid domain is required.',
            ];
        }

        try {
            $this->request('Email', 'delete_domain_forwarder', 'post', [
                'domain' => $domain,
            ]);

            return [
                'success' => true,
                'message' => 'Domain forwarder deleted successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to delete domain forwarder.');
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

    /**
     * @return array{success: bool, message: string, cron_jobs?: array<int, array<string, mixed>>, count?: int, data?: array<string, mixed>}
     */
    public function listCronJobs(): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        try {
            $result = $this->request('Cron', 'listcron');
            $jobs = $this->normalizeCronJobs($result['data'] ?? []);

            return [
                'success' => true,
                'message' => 'Cron jobs retrieved successfully.',
                'cron_jobs' => $jobs,
                'count' => count($jobs),
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to list cron jobs.');
        }
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    public function addCronJob(string $minute, string $hour, string $day, string $month, string $weekday, string $command): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        if (trim($command) === '') {
            return [
                'success' => false,
                'message' => 'Cron command is required.',
            ];
        }

        try {
            $this->request('Cron', 'add_line', 'post', [
                'minute' => $minute,
                'hour' => $hour,
                'day' => $day,
                'month' => $month,
                'weekday' => $weekday,
                'command' => $command,
            ]);

            return [
                'success' => true,
                'message' => 'Cron job added successfully.',
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, 'Failed to add cron job.');
        }
    }

    /**
     * @return array{success: bool, message: string, action?: string, data?: array<string, mixed>}
     */
    public function ensureCronJob(string $minute, string $hour, string $day, string $month, string $weekday, string $command): array
    {
        $listResult = $this->listCronJobs();
        if (! ($listResult['success'] ?? false)) {
            return $listResult;
        }

        $jobs = $listResult['cron_jobs'] ?? [];

        $alreadyExists = collect($jobs)->contains(function (array $job) use ($minute, $hour, $day, $month, $weekday, $command): bool {
            return trim((string) ($job['minute'] ?? '')) === $minute
                && trim((string) ($job['hour'] ?? '')) === $hour
                && trim((string) ($job['day'] ?? '')) === $day
                && trim((string) ($job['month'] ?? '')) === $month
                && trim((string) ($job['weekday'] ?? '')) === $weekday
                && trim((string) ($job['command'] ?? '')) === trim($command);
        });

        if ($alreadyExists) {
            return [
                'success' => true,
                'message' => 'Cron job already exists.',
                'action' => 'exists',
            ];
        }

        $addResult = $this->addCronJob($minute, $hour, $day, $month, $weekday, $command);
        if (! ($addResult['success'] ?? false)) {
            return $addResult;
        }

        return [
            'success' => true,
            'message' => 'Cron job added.',
            'action' => 'added',
        ];
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

        $url = $this->config->buildApiUrl($module, $function);
        $normalizedMethod = strtolower($method) === 'post' ? 'post' : 'get';

        Log::debug('cPanel API request starting.', [
            'module' => $module,
            'function' => $function,
            'method' => strtoupper($normalizedMethod),
            'url' => $url,
            'request' => $this->sanitizeContext($params),
            'timeout' => $this->config->timeout,
            'connect_timeout' => $this->config->connectTimeout,
            'verify_ssl' => $this->config->verifySsl,
        ]);

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

            $response = $normalizedMethod === 'post'
                ? $pending->asForm()->post($url, $params)
                : $pending->get($url, $params);

            $payload = $response->json();
            if (! is_array($payload)) {
                $payload = [];
            }

            Log::debug('cPanel API response received.', [
                'module' => $module,
                'function' => $function,
                'method' => strtoupper($normalizedMethod),
                'url' => $url,
                'http_status' => $response->status(),
                'cpanel_status' => (int) ($payload['status'] ?? 0),
                'data_count' => is_array($payload['data'] ?? null) ? count($payload['data']) : null,
            ]);

            if (! $response->successful()) {
                throw new CpanelRequestException(
                    message: 'cPanel request failed with HTTP status '.$response->status().'.',
                    context: [
                        'http_status' => $response->status(),
                        'module' => $module,
                        'function' => $function,
                        'method' => strtoupper($normalizedMethod),
                        'url' => $url,
                        'payload' => $this->sanitizeContext($payload),
                        'request' => $this->sanitizeContext($params),
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
                        'method' => strtoupper($normalizedMethod),
                        'url' => $url,
                        'payload' => $this->sanitizeContext($payload),
                        'request' => $this->sanitizeContext($params),
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
                    'method' => strtoupper($normalizedMethod),
                    'url' => $url,
                    'request' => $this->sanitizeContext($params),
                    'error' => $this->sanitizeString($exception->getMessage()),
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

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeCronJobs(array $records): array
    {
        return collect($records)
            ->map(function (array $record): array {
                return [
                    'linekey' => (string) ($record['linekey'] ?? ''),
                    'minute' => (string) ($record['minute'] ?? ''),
                    'hour' => (string) ($record['hour'] ?? ''),
                    'day' => (string) ($record['day'] ?? ''),
                    'month' => (string) ($record['month'] ?? ''),
                    'weekday' => (string) ($record['weekday'] ?? ''),
                    'command' => (string) ($record['command'] ?? ''),
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
            return $this->sanitizeLocalPart($normalized);
        }

        [$localPart] = explode('@', $normalized, 2);

        return $this->sanitizeLocalPart($localPart);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function extractMailboxParts(string $email): array
    {
        $normalized = trim($email);
        if ($normalized === '') {
            return ['', (string) $this->config->domain];
        }

        if (! str_contains($normalized, '@')) {
            $localPart = $this->sanitizeLocalPart($normalized);
            $domain = $this->sanitizeDomain((string) $this->config->domain);

            return [$localPart, $domain];
        }

        [$localPart, $domain] = explode('@', $normalized, 2);

        $localPart = $this->sanitizeLocalPart($localPart);
        $domain = $this->sanitizeDomain($domain !== '' ? $domain : (string) $this->config->domain);

        return [$localPart, $domain];
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeForwarders(array $records): array
    {
        return collect($records)
            ->map(function (array $record): array {
                return [
                    'email' => (string) ($record['email'] ?? ''),
                    'forward_to' => (string) ($record['forward'] ?? $record['dest'] ?? ''),
                    'uri' => (string) ($record['uri'] ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{success: bool, message: string, data?: array<string, mixed>}
     */
    protected function updateMailboxLoginState(string $email, bool $suspend): array
    {
        if (! $this->isConfigured()) {
            return $this->configurationErrorResponse();
        }

        [$localPart, $domain] = $this->extractMailboxParts($email);
        if ($localPart === '') {
            return [
                'success' => false,
                'message' => 'Valid email is required.',
            ];
        }

        $function = $suspend ? 'suspend_login' : 'unsuspend_login';
        $successMessage = $suspend
            ? 'Email account suspended successfully.'
            : 'Email account unsuspended successfully.';
        $failureMessage = $suspend
            ? 'Failed to suspend email account.'
            : 'Failed to unsuspend email account.';

        try {
            $this->request('Email', $function, 'post', [
                'email' => $localPart,
                'domain' => $domain,
            ]);

            return [
                'success' => true,
                'message' => $successMessage,
            ];
        } catch (CpanelRequestException $exception) {
            return $this->requestErrorResponse($exception, $failureMessage);
        }
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
        Log::warning('cPanel API skipped because configuration is incomplete.', [
            'missing_fields' => $this->missingConfigurationFields(),
            'url' => $this->config->url,
            'domain' => $this->config->domain,
        ]);

        return [
            'success' => false,
            'message' => 'cPanel configuration is incomplete.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function missingConfigurationFields(): array
    {
        $missing = [];

        if ($this->config->url === null) {
            $missing[] = 'url';
        }

        if ($this->config->username === null) {
            $missing[] = 'username';
        }

        if ($this->config->apiToken === null) {
            $missing[] = 'api_token';
        }

        if ($this->config->domain === null) {
            $missing[] = 'domain';
        }

        return $missing;
    }

    /**
     * @return array{success: false, message: string, data: array<string, mixed>}
     */
    protected function requestErrorResponse(Throwable $exception, string $fallbackMessage): array
    {
        if ($exception instanceof CpanelRequestException) {
            $sanitizedContext = $this->sanitizeContext($exception->context);
            $sanitizedMessage = $this->sanitizeString($exception->getMessage());

            Log::warning('cPanel API request failed.', [
                'message' => $sanitizedMessage,
                'context' => $sanitizedContext,
            ]);

            return [
                'success' => false,
                'message' => $sanitizedMessage !== '' ? $sanitizedMessage : $fallbackMessage,
                'data' => $sanitizedContext,
            ];
        }

        $sanitizedError = $this->sanitizeString($exception->getMessage());

        Log::warning('Unexpected cPanel service error.', ['error' => $sanitizedError]);

        return [
            'success' => false,
            'message' => $fallbackMessage,
            'data' => ['error' => $sanitizedError],
        ];
    }

    private function sanitizeLocalPart(string $localPart): string
    {
        $normalized = trim(strtolower($localPart));
        if ($normalized === '') {
            return '';
        }

        if (strlen($normalized) > 64) {
            return '';
        }

        if (! preg_match("/^(?!\.)(?!.*\.\.)([a-z0-9!#$%&'*+\/=?^_`{|}~.-]+)(?<!\.)$/", $normalized)) {
            return '';
        }

        return $normalized;
    }

    private function sanitizeDomain(string $domain): string
    {
        $normalized = trim(strtolower($domain));
        if ($normalized === '') {
            return '';
        }

        return filter_var($normalized, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ? $normalized
            : '';
    }

    private function sanitizeString(string $value): string
    {
        $value = preg_replace('/(cpanel\s+[^:\s]+:)([^\s]+)/i', '$1[REDACTED]', $value) ?: $value;
        $value = preg_replace('/((api[_-]?token|password|authorization|secret)\s*[=:]\s*)([^,\s]+)/i', '$1[REDACTED]', $value) ?: $value;

        return $value;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $keyString = strtolower((string) $key);

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);

                continue;
            }

            if (preg_match('/password|api[_-]?token|authorization|secret|token/', $keyString) === 1) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
