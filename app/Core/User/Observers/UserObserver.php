<?php

namespace App\Core\User\Observers;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\User\Models\User;
use App\Core\User\Notifications\UserInvitationNotification;

class UserObserver
{
    public function __construct(
        protected CpanelMailboxManager $mailboxManager
    ) {}

    public function creating(User $user): void
    {
        if ($this->isBlank($user->company_email ?? null)) {
            $user->company_email = $this->mailboxManager->resolveCompanyEmail($user->username);
        }
    }

    public function created(User $user): void
    {
        $this->mailboxManager->provisionForUser($user);

        $user->notify(new UserInvitationNotification);
    }

    public function updating(User $user): void
    {
        if (! $user->isDirty('username')) {
            return;
        }

        $domainGeneratedOriginal = $this->mailboxManager->resolveCompanyEmail($user->getOriginal('username'));
        $currentCompanyEmail = trim((string) ($user->company_email ?? ''));

        if ($this->isBlank($currentCompanyEmail) || $currentCompanyEmail === $domainGeneratedOriginal) {
            $user->company_email = $this->mailboxManager->resolveCompanyEmail($user->username);
        }
    }

    public function updated(User $user): void
    {
        $this->mailboxManager->syncUsernameChange($user, $user->getOriginal('company_email'));
    }

    public function deleted(User $user): void
    {
        $this->mailboxManager->deprovisionForUser($user);
    }

    private function isBlank(?string $value): bool
    {
        return trim((string) $value) === '';
    }
}
