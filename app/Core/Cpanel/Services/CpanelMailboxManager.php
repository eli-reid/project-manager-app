<?php

namespace App\Core\Cpanel\Services;

use App\Core\User\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CpanelMailboxManager
{
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

    public function provisionForUser(User $user): void
    {
        if (! $this->cpanelService->configuration()->autoCreateEmails) {
            return;
        }

        if (! $this->cpanelService->isConfigured()) {
            return;
        }

        $username = trim((string) $user->username);
        if ($username === '') {
            return;
        }

        $result = $this->cpanelService->createEmailAccount(
            emailUsername: $username,
            password: Str::password(24)
        );

        if (! $result['success']) {
            Log::warning('Failed to provision cPanel mailbox for user.', [
                'user_id' => $user->id,
                'username' => $username,
                'message' => $result['message'] ?? null,
                'data' => $result['data'] ?? [],
            ]);
        }
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

        $result = $this->cpanelService->deleteEmailAccount($previousCompanyEmail);
        if (! $result['success']) {
            Log::warning('Failed to remove previous cPanel mailbox after username change.', [
                'user_id' => $user->id,
                'previous_company_email' => $previousCompanyEmail,
                'message' => $result['message'] ?? null,
                'data' => $result['data'] ?? [],
            ]);
        }
    }

    public function deprovisionForUser(User $user): void
    {
        if (! $this->cpanelService->configuration()->autoDeleteEmails) {
            return;
        }

        if (! $this->cpanelService->isConfigured()) {
            return;
        }

        $companyEmail = trim((string) $user->company_email);
        if ($companyEmail === '') {
            return;
        }

        $result = $this->cpanelService->deleteEmailAccount($companyEmail);
        if (! $result['success']) {
            Log::warning('Failed to deprovision cPanel mailbox for deleted user.', [
                'user_id' => $user->id,
                'company_email' => $companyEmail,
                'message' => $result['message'] ?? null,
                'data' => $result['data'] ?? [],
            ]);
        }
    }
}
