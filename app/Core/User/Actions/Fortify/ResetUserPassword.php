<?php

namespace App\Core\User\Actions\Fortify;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\User\Concerns\PasswordValidationRules;
use App\Core\User\Models\User;
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

        $user->forceFill([
            'password' => $input['password'],
            'password_change_required' => false,
        ])->save();

        $this->mailboxManager->syncPasswordForUser($user, $input['password']);
    }
}
