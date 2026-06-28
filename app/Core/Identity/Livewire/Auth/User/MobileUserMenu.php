<?php

namespace App\Core\Identity\Livewire\Auth\User;

use App\PlugIns\Cpanel\Services\CpanelMailboxManager;
use App\PlugIns\Cpanel\Services\CpanelService;
use App\Core\Identity\Livewire\Auth\User\Concerns\LaunchesMailbox;
use Illuminate\View\View;
use Livewire\Component;

/**
 * @method mixed dispatch(string $event, mixed ...$params)
 */
class MobileUserMenu extends Component
{
    use LaunchesMailbox;

    protected CpanelService $cpanelService;

    protected CpanelMailboxManager $mailboxManager;

    public function boot(CpanelService $cpanelService, CpanelMailboxManager $mailboxManager): void
    {
        $this->cpanelService = $cpanelService;
        $this->mailboxManager = $mailboxManager;
    }

    public function launchMailbox(): void
    {
        $payload = $this->mailboxLaunchPayload();

        $mode = (string) ($payload['mode'] ?? 'fallback_redirect');

        if ($mode === 'post_handshake') {
            $this->dispatch(
                'open-webmail',
                mode: 'post_handshake',
                login_url: (string) ($payload['login_url'] ?? ''),
                session: (string) ($payload['session'] ?? ''),
            );

            return;
        }

        if ($mode === 'direct_url') {
            $this->dispatch('open-webmail', mode: 'direct_url', url: (string) ($payload['url'] ?? ''));

            return;
        }

        $this->dispatch('open-webmail', mode: 'fallback_redirect', url: (string) ($payload['url'] ?? route('webmail.redirect')));
    }

    public function render(): View
    {
        return view('core-user::livewire.auth.user.mobile-user-menu');
    }
}
