<?php

namespace App\Core\Identity\Livewire\Auth\User\Concerns;

use App\PlugIns\Cpanel\Services\CpanelMailboxManager;
use App\PlugIns\Cpanel\Services\CpanelService;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Auth;

trait LaunchesMailbox
{
    protected CpanelService $cpanelService;

    protected CpanelMailboxManager $mailboxManager;

    /**
     * @return array{mode:string,url?:string,login_url?:string,session?:string}
     */
    protected function mailboxLaunchPayload(): array
    {
        $fallbackUrl = route('webmail.redirect');

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User || ! $this->cpanelService->isConfigured()) {
            return [
                'mode' => 'fallback_redirect',
                'url' => $fallbackUrl,
            ];
        }

        $companyEmail = trim((string) ($user->company_email ?? ''));
        $resolvedCompanyEmail = trim((string) $this->mailboxManager->resolveCompanyEmail($user->username));

        $candidateEmails = array_values(array_filter(array_unique([
            $companyEmail,
            $resolvedCompanyEmail,
        ])));

        if ($candidateEmails === []) {
            return [
                'mode' => 'fallback_redirect',
                'url' => $fallbackUrl,
            ];
        }

        foreach ($candidateEmails as $candidateEmail) {
            $result = $this->cpanelService->createWebmailSession($candidateEmail);

            if (! ($result['success'] ?? false)) {
                continue;
            }

            if (isset($result['login_url'], $result['session'])) {
                return [
                    'mode' => 'post_handshake',
                    'login_url' => (string) $result['login_url'],
                    'session' => (string) $result['session'],
                ];
            }

            if (isset($result['url'])) {
                return [
                    'mode' => 'direct_url',
                    'url' => (string) $result['url'],
                ];
            }

            $fallbackUrl = $this->cpanelService->webmailRedirectUrl($candidateEmail);
        }

        return [
            'mode' => 'fallback_redirect',
            'url' => $fallbackUrl,
        ];
    }
}
