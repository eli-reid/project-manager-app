<?php

namespace App\Core\Auth\User\Actions\Admin;

use App\Core\Identity\Models\User;
use App\Core\Identity\Notifications\UserInvitationNotification;
use App\Core\Zoom\Services\ZoomSmsConsentService;
use App\Core\Zoom\Services\ZoomSmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateInvitedUser
{
    /**
     * @param  array{first_name: string, last_name: string, phone: string|null, username: string, email: string, is_active: bool}  $attributes
     * @param  array<int, string>  $roleIds
     */
    public function handle(array $attributes, array $roleIds): User
    {
        $temporaryPassword = Str::random(16);

        $user = DB::transaction(function () use ($attributes, $roleIds, $temporaryPassword): User {
            $user = new User([
                'first_name' => $attributes['first_name'],
                'last_name' => $attributes['last_name'],
                'phone' => $attributes['phone'] ?? null,
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

        // If a phone number exists, attempt to send a Zoom SMS consent request.
        try {
            $phone = $user->phone ?? null;

            if ($phone !== null && $phone !== '') {
                /** @var ZoomSmsService $smsService */
                $smsService = app(ZoomSmsService::class);

                if ($smsService->isConfigured()) {
                    /** @var ZoomSmsConsentService $consentService */
                    $consentService = app(ZoomSmsConsentService::class);

                    // This will send the consent-request message and mark the number pending.
                    $consentService->requestConsent($phone, $smsService);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to request SMS consent for invited user', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return $user;
    }
}
