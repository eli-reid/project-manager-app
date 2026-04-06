<?php

namespace App\Core\Cpanel\Services;

use App\Core\Cpanel\Jobs\PerformMailboxWriteOperation;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class CpanelMailboxManager
{
    public const OPERATION_PROVISION = 'provision';

    public const OPERATION_DELETE = 'delete';

    public const OPERATION_SYNC_PASSWORD = 'sync-password';

    public function __construct(
        protected CpanelService $cpanelService
    ) {}

    public function resolveCompanyEmail(?string $username): ?string
    {
        $username = trim((string) $username);
        if ($username === '') {
            return null;
        }

        $domain = (string) ($this->cpanelService->configuration()->domain ?? '');
        if ($domain === '') {
            return null;
        }

        return $username.'@'.$domain;
    }

    public function provisionForUser(User $user, ?string $password = null): void
    {
        $configuration = $this->cpanelService->configuration();
        if (! $configuration->autoCreateEmails) {
            return;
        }

        if (! $this->cpanelService->isConfigured()) {
            return;
        }

        $username = trim((string) $user->username);
        if ($username === '') {
            return;
        }

        $this->dispatchOrRun(
            operation: self::OPERATION_PROVISION,
            payload: [
                'user_id' => (string) $user->id,
                'username' => $username,
                'password' => $password,
            ],
            idempotencyKey: 'provision:'.(string) $user->id.':'.$username,
        );
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function generateForUser(User $user): array
    {
        if (! $this->cpanelService->isConfigured()) {
            return [
                'success' => false,
                'message' => 'cPanel configuration is incomplete.',
            ];
        }

        $username = trim((string) $user->username);
        if ($username === '') {
            return [
                'success' => false,
                'message' => 'User must have a username before generating company email.',
            ];
        }

        $result = $this->cpanelService->createEmailAccount(
            emailUsername: $username,
            password: Str::password(24)
        );

        $resolvedCompanyEmail = $this->resolveCompanyEmail($username);
        $cpanelMessage = strtolower((string) ($result['message'] ?? ''));
        $accountAlreadyExists = str_contains($cpanelMessage, 'already exists');

        if (! $result['success'] && ! $accountAlreadyExists) {
            return [
                'success' => false,
                'message' => (string) ($result['message'] ?? 'Unable to generate company email account.'),
            ];
        }

        if ($resolvedCompanyEmail !== null) {
            $user->forceFill(['company_email' => $resolvedCompanyEmail])->save();
        }

        if ($accountAlreadyExists) {
            return [
                'success' => true,
                'message' => 'Company email already exists. User record has been synchronized.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Company email generated successfully.',
        ];
    }

    public function syncUsernameChange(User $user, ?string $previousCompanyEmail): void
    {
        if (! $user->wasChanged('username')) {
            return;
        }

        $this->provisionForUser($user);

        if (! $this->cpanelService->configuration()->autoDeleteEmails) {
            return;
        }

        if (! $this->cpanelService->isConfigured()) {
            return;
        }

        $previousCompanyEmail = trim((string) $previousCompanyEmail);
        $currentCompanyEmail = trim((string) $user->company_email);

        if ($previousCompanyEmail === '' || $previousCompanyEmail === $currentCompanyEmail) {
            return;
        }

        $this->dispatchOrRun(
            operation: self::OPERATION_DELETE,
            payload: [
                'user_id' => (string) $user->id,
                'email' => $previousCompanyEmail,
            ],
            idempotencyKey: 'delete:'.$previousCompanyEmail,
        );
    }

    public function deprovisionForUser(User $user): void
    {
        $configuration = $this->cpanelService->configuration();
        if (! $configuration->autoDeleteEmails) {
            return;
        }

        if (! $this->cpanelService->isConfigured()) {
            return;
        }

        $companyEmail = trim((string) $user->company_email);
        if ($companyEmail === '') {
            return;
        }

        $this->dispatchOrRun(
            operation: self::OPERATION_DELETE,
            payload: [
                'user_id' => (string) $user->id,
                'email' => $companyEmail,
            ],
            idempotencyKey: 'delete:'.$companyEmail,
        );
    }

    public function syncPasswordForUser(User $user, string $password): void
    {
        $configuration = $this->cpanelService->configuration();
        if (! $configuration->syncUserPasswords) {
            return;
        }

        if (! $this->cpanelService->isConfigured()) {
            return;
        }

        $password = trim($password);
        if ($password === '') {
            return;
        }

        $companyEmail = trim((string) ($user->company_email ?? ''));
        if ($companyEmail === '') {
            $companyEmail = trim((string) $this->resolveCompanyEmail($user->username));
        }

        if ($companyEmail === '') {
            return;
        }

        $this->dispatchOrRun(
            operation: self::OPERATION_SYNC_PASSWORD,
            payload: [
                'user_id' => (string) $user->id,
                'email' => $companyEmail,
                'password' => $password,
            ],
            idempotencyKey: null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function executeWriteOperation(string $operation, array $payload, bool $fromQueue = false): void
    {
        if ($this->isCooldownActive()) {
            $this->incrementTelemetry('skipped', $operation);

            return;
        }

        $result = match ($operation) {
            self::OPERATION_PROVISION => $this->cpanelService->createEmailAccount(
                emailUsername: (string) ($payload['username'] ?? ''),
                password: (string) ($payload['password'] ?? Str::password(24)),
            ),
            self::OPERATION_DELETE => $this->cpanelService->deleteEmailAccount((string) ($payload['email'] ?? '')),
            self::OPERATION_SYNC_PASSWORD => $this->cpanelService->updateEmailPassword(
                email: (string) ($payload['email'] ?? ''),
                password: (string) ($payload['password'] ?? ''),
            ),
            default => [
                'success' => false,
                'message' => 'Unknown cPanel mailbox operation.',
            ],
        };

        $normalizedResult = $this->normalizeResultForIdempotency($operation, $result);

        if (($normalizedResult['success'] ?? false) === true) {
            $this->incrementTelemetry('success', $operation);
            $this->resetConsecutiveFailures();

            return;
        }

        $this->incrementTelemetry('failure', $operation);
        $consecutiveFailures = $this->incrementConsecutiveFailures();
        $failureMessage = (string) ($normalizedResult['message'] ?? 'Unknown cPanel mailbox failure.');

        if ($this->shouldOpenCooldown($consecutiveFailures)) {
            $this->openCooldown();
        }

        Log::warning('cPanel write-side mailbox operation failed.', [
            'operation' => $operation,
            'user_id' => $payload['user_id'] ?? null,
            'email' => $payload['email'] ?? null,
            'message' => $failureMessage,
        ]);

        if ($fromQueue && $this->isTransientFailure($failureMessage)) {
            throw new RuntimeException($failureMessage);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatchOrRun(string $operation, array $payload, ?string $idempotencyKey): void
    {
        if ($idempotencyKey !== null && $this->isDuplicateOperation($operation, $idempotencyKey)) {
            $this->incrementTelemetry('skipped', $operation);

            return;
        }

        if ($this->cpanelService->configuration()->queueWriteOperations) {
            PerformMailboxWriteOperation::dispatch($operation, $payload);

            return;
        }

        $this->executeWriteOperation(operation: $operation, payload: $payload);
    }

    private function isDuplicateOperation(string $operation, string $idempotencyKey): bool
    {
        $ttl = max($this->cpanelService->configuration()->idempotencyTtlSeconds, 1);
        $cacheKey = $this->telemetryPrefix().'.idempotency.'.$operation.'.'.sha1($idempotencyKey);

        return ! Cache::add($cacheKey, (string) now()->timestamp, now()->addSeconds($ttl));
    }

    /**
     * @param  array{success: bool, message?: string}  $result
     * @return array{success: bool, message?: string}
     */
    private function normalizeResultForIdempotency(string $operation, array $result): array
    {
        if (($result['success'] ?? false) === true) {
            return $result;
        }

        $message = strtolower((string) ($result['message'] ?? ''));
        $alreadyExists = $operation === self::OPERATION_PROVISION
            && str_contains($message, 'already exists');
        $alreadyMissing = $operation === self::OPERATION_DELETE
            && (str_contains($message, 'does not exist') || str_contains($message, 'not found'));

        if ($alreadyExists || $alreadyMissing) {
            return [
                'success' => true,
                'message' => (string) ($result['message'] ?? null),
            ];
        }

        return $result;
    }

    private function isTransientFailure(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'unable to connect')
            || str_contains($normalized, 'timeout')
            || str_contains($normalized, 'temporar')
            || str_contains($normalized, 'connection');
    }

    private function shouldOpenCooldown(int $consecutiveFailures): bool
    {
        return $consecutiveFailures >= max($this->cpanelService->configuration()->failureThreshold, 1);
    }

    private function openCooldown(): void
    {
        $seconds = max($this->cpanelService->configuration()->cooldownSeconds, 1);

        Cache::put(
            $this->telemetryPrefix().'.cooldown_until',
            now()->addSeconds($seconds)->timestamp,
            now()->addSeconds($seconds)
        );
    }

    private function isCooldownActive(): bool
    {
        $until = (int) Cache::get($this->telemetryPrefix().'.cooldown_until', 0);

        return $until > now()->timestamp;
    }

    private function incrementConsecutiveFailures(): int
    {
        return (int) Cache::increment($this->telemetryPrefix().'.consecutive_failures');
    }

    private function resetConsecutiveFailures(): void
    {
        Cache::forget($this->telemetryPrefix().'.consecutive_failures');
    }

    private function incrementTelemetry(string $status, string $operation): void
    {
        Cache::increment($this->telemetryPrefix().'.'.$status.'.'.$operation);
    }

    private function telemetryPrefix(): string
    {
        $prefix = trim($this->cpanelService->configuration()->telemetryKeyPrefix);

        return $prefix !== '' ? $prefix : 'cpanel.telemetry';
    }
}
