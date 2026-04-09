<?php

namespace App\Core\Cpanel\Http\Controllers;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Cpanel\Services\CpanelService;
use App\Core\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebmailController
{
    public function __construct(
        protected CpanelService $cpanelService,
        protected CpanelMailboxManager $mailboxManager
    ) {}

    public function redirect(Request $request): RedirectResponse|Response
    {
        /** @var User $user */
        $user = $request->user();

        $companyEmail = trim((string) ($user->company_email ?? ''));
        if ($companyEmail === '') {
            $companyEmail = (string) $this->mailboxManager->resolveCompanyEmail($user->username);
        }

        if ($companyEmail === '') {
            return redirect()->route('dashboard')->with('error', 'No company email is configured for your account.');
        }

        if (! $this->cpanelService->isConfigured()) {
            return redirect()->route('dashboard')->with('error', 'Webmail is not configured.');
        }

        $result = $this->cpanelService->createWebmailSession($companyEmail);

        if ($result['success'] && isset($result['login_url'], $result['session'])) {
            return response(view('cpanel::webmail.auto-login', [
                'loginUrl' => $result['login_url'],
                'session' => $result['session'],
            ]));
        }

        if ($result['success'] && isset($result['url'])) {
            return redirect()->away($result['url']);
        }

        return redirect()->away($this->cpanelService->webmailRedirectUrl($companyEmail));
    }
}
