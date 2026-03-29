<?php

namespace App\Core\User\Actions\Admin;

use App\Core\User\Models\User;
use App\Core\User\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateInvitedUser
{
    /**
     * @param  array{first_name: string, last_name: string, username: string, email: string, is_active: bool}  $attributes
     * @param  array<int, string>  $roleIds
     */
    public function handle(array $attributes, array $roleIds): User
    {
        $temporaryPassword = Str::random(16);

        $user = DB::transaction(function () use ($attributes, $roleIds, $temporaryPassword): User {
            $user = new User([
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'username' => $attributes['username'],
                'email' => $attributes['email'],
                'password' => $temporaryPassword,
                'is_active' => (bool) $attributes['is_active'],
                'password_change_required' => true,
                'is_admin' => false,
                'is_built_in' => false,
            ]);

            $user->mailboxProvisioningPassword = $temporaryPassword;
            $user->save();

            $user->roles()->sync($roleIds);
            $user->flushAuthorizationCache();
            User::bumpPermissionCacheVersion();

            return $user;
        });

        $user->notify(new UserInvitationNotification($temporaryPassword));

        return $user;
    }
}
