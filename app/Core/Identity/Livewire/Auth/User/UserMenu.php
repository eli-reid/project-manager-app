<?php

namespace App\Core\Identity\Livewire\Auth\User;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Cpanel\Services\CpanelService;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class UserMenu extends Component
{
    protected CpanelService $cpanelService;

    protected CpanelMailboxManager $mailboxManager;

    public string $variant = 'desktop';

    public function boot(CpanelService $cpanelService, CpanelMailboxManager $mailboxManager): void
    {
        $this->cpanelService = $cpanelService;
        $this->mailboxManager = $mailboxManager;
    }

    public function mount(string $variant = 'desktop'): void
    {
        $this->variant = in_array($variant, ['desktop', 'mobile'], true)
            ? $variant
            : 'desktop';
    }

    public function launchMailbox(): void
    {
        $fallbackUrl = route('webmail.redirect');

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User || ! $this->cpanelService->isConfigured()) {
            $this->dispatch('open-webmail', mode: 'fallback_redirect', url: $fallbackUrl);

            return;
        }

        $companyEmail = trim((string) ($user->company_email ?? ''));
        $resolvedCompanyEmail = trim((string) $this->mailboxManager->resolveCompanyEmail($user->username));

        $candidateEmails = array_values(array_filter(array_unique([
            $companyEmail,
            $resolvedCompanyEmail,
        ])));

        if ($candidateEmails === []) {
            $this->dispatch('open-webmail', mode: 'fallback_redirect', url: $fallbackUrl);

            return;
        }

        foreach ($candidateEmails as $candidateEmail) {
            $result = $this->cpanelService->createWebmailSession($candidateEmail);

            if (! ($result['success'] ?? false)) {
                continue;
            }

            if (isset($result['login_url'], $result['session'])) {
                $this->dispatch(
                    'open-webmail',
                    mode: 'post_handshake',
                    login_url: (string) $result['login_url'],
                    session: (string) $result['session']
                );

                return;
            }

            if (isset($result['url'])) {
                $this->dispatch('open-webmail', mode: 'direct_url', url: (string) $result['url']);

                return;
            }

            $fallbackUrl = $this->cpanelService->webmailRedirectUrl($candidateEmail);
        }

        $this->dispatch('open-webmail', mode: 'fallback_redirect', url: $fallbackUrl);
    }

    public function render(): View
    {
        return view($this->variant === 'mobile'
            ? 'core-user::livewire.auth.user.mobile-user-menu'
            : 'core-user::livewire.auth.user.desktop-user-menu');
    }
}
