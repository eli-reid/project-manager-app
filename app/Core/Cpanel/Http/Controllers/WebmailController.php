<?php

namespace App\Core\Cpanel\Http\Controllers;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Cpanel\Services\CpanelService;
use App\Core\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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
        $resolvedCompanyEmail = trim((string) $this->mailboxManager->resolveCompanyEmail($user->username));

        $candidateEmails = array_values(array_filter(array_unique([
            $companyEmail,
            $resolvedCompanyEmail,
        ])));

        if ($candidateEmails === []) {
            return redirect()->route('dashboard')->with('error', 'No company email is configured for your account.');
        }

        if (! $this->cpanelService->isConfigured()) {
            return redirect()->route('dashboard')->with('error', 'Webmail is not configured.');
        }

        $result = null;
        $selectedEmail = $candidateEmails[0];

        foreach ($candidateEmails as $candidateEmail) {
            $selectedEmail = $candidateEmail;
            $result = $this->cpanelService->createWebmailSession($candidateEmail);

            if (($result['success'] ?? false) === true) {
                break;
            }

            Log::notice('Webmail session attempt failed for candidate email.', [
                'user_id' => (string) $user->id,
                'candidate_email' => $candidateEmail,
                'message' => $result['message'] ?? null,
            ]);
        }

        if ($result === null) {
            return redirect()->route('dashboard')->with('error', 'Unable to launch webmail.');
        }

        if ($result['success'] && isset($result['login_url'], $result['session'])) {
            return response(view('cpanel::webmail.auto-login', [
                'loginUrl' => $result['login_url'],
                'session' => $result['session'],
            ]))
                // Prevent stale one-time sessions from being cached by browsers/proxies.
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        if ($result['success'] && isset($result['url'])) {
            return redirect()->away($result['url']);
        }

        return redirect()->away($this->cpanelService->webmailRedirectUrl($selectedEmail));
    }
}
