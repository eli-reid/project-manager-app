<?php

namespace App\Domains\Timecards\Services;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Database\Eloquent\Collection;

class TimecardNotificationRecipientService
{
    /**
     * @return Collection<int, User>
     */
    public function approversForSubmittedTimecard(Timecard $timecard): Collection
    {
        return User::query()
            ->where('id', '!=', $timecard->user_id)
            ->where('is_active', true)
            ->whereHas('roles', function ($roleQuery): void {
                $roleQuery
                    ->where('roles.is_active', true)
                    ->whereHas('permissions', function ($permissionQuery): void {
                        $permissionQuery
                            ->where('resource', 'timecards')
                            ->where('action', 'approve');
                    });
            })
            ->get();
    }
}
