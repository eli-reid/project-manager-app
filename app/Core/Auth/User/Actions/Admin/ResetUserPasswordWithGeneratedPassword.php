<?php

namespace App\Core\Auth\User\Actions\Admin;

use App\Core\Audit\Contracts\AuditLoggerContract;
use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Identity\Models\User;
use App\Core\Identity\Notifications\UserInvitationNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResetUserPasswordWithGeneratedPassword
{
    public function __construct(
        protected CpanelMailboxManager $mailboxManager
    ) {}

    public function handle(User $user, ?Authenticatable $actor = null, string $auditAction = 'admin.users.password.reset'): void
    {
        $temporaryPassword = Str::random(16);

        $before = [
            'password_change_required' => (bool) $user->password_change_required,
        ];

        $user->forceFill([
            'password' => $temporaryPassword,
            'password_change_required' => true,
        ])->save();

        app(AuditLoggerContract::class)->record($auditAction, $user, [
            'before' => $before,
            'after' => [
                'password_change_required' => true,
            ],
        ], $actor);

        if ($user->company_email !== null) {
            try {
                $this->mailboxManager->syncPasswordForUser($user, $temporaryPassword);
            } catch (\Throwable $exception) {
                Log::warning('Failed to sync admin-generated password reset to cPanel mailbox.', [
                    'user_id' => (string) $user->id,
                    'company_email' => $user->company_email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $user->notify(new UserInvitationNotification($temporaryPassword));
    }
}
