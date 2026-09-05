<?php

namespace App\Core\Identity\Actions\Fortify;

use App\Core\Identity\Concerns\PasswordValidationRules;
use App\Core\Identity\Concerns\ProfileValidationRules;
use App\Core\Identity\Models\User;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'phone' => filled($input['phone'] ?? null) ? $input['phone'] : null,
                'username' => $input['username'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            // Pass only a namespaced plugin payload (e.g. payroll fields under 'payroll')
            $pluginPayload = $input['payroll'] ?? [];

            event(new UserRegistered($user, $pluginPayload));

            return $user;
        });
    }
}
