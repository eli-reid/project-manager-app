<?php

namespace App\Core\Auth\User\Livewire\Layout;

use App\Core\Cpanel\Services\CpanelService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class AppSidebar extends Component
{
    public function render(CpanelService $cpanelService): View
    {
        $user = Auth::user();
        $emailAddress = trim((string) ($user?->company_email ?? $user?->username ?? ''));

        return view('auth-user::livewire.layout.app-sidebar', [
            'showWebmailLink' => Auth::check()
                && $cpanelService->isConfigured()
                && filled($emailAddress),
        ]);
    }
}
