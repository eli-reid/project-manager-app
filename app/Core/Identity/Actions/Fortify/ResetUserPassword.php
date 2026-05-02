<?php

namespace App\Core\Identity\Actions\Fortify;

use App\Core\Audit\Services\AuditLogger;
use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Concerns\PasswordValidationRules;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(
        protected CpanelMailboxManager $mailboxManager
    ) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $before = [
            'password_change_required' => (bool) $user->password_change_required,
        ];

        $user->forceFill([
            'password' => $input['password'],
            'password_change_required' => false,
        ])->save();

        app(AuditLogger::class)->record('auth.password.reset', $user, [
            'before' => $before,
            'after' => [
                'password_change_required' => false,
            ],
        ], $user);

        if ($user->company_email !== null) {
            try {
                $this->mailboxManager->syncPasswordForUser($user, $input['password']);
            } catch (\Throwable $exception) {
                Log::warning('Failed to sync reset password to cPanel mailbox.', [
                    'user_id' => (string) $user->id,
                    'company_email' => $user->company_email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }
}
