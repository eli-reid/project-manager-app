<?php

namespace App\Domains\Timecards\Policies;

use App\Core\User\Models\User;
use App\Domains\Timecards\Models\Timecard;

class TimecardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('timecards.view') || $user->hasPermission('timecards.view-all');
    }

    public function view(User $user, Timecard $timecard): bool
    {
        if ($user->hasPermission('timecards.view-all')) {
            return true;
        }

        return $timecard->user_id === $user->id && $user->hasPermission('timecards.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('timecards.create');
    }

    public function update(User $user, Timecard $timecard): bool
    {
        if ($user->hasPermission('timecards.edit') && $user->hasPermission('timecards.view-all')) {
            return true;
        }

        return $timecard->user_id === $user->id
            && $timecard->status === Timecard::STATUS_DRAFT
            && $user->hasPermission('timecards.edit');
    }

    public function delete(User $user, Timecard $timecard): bool
    {
        if ($user->hasPermission('timecards.delete') && $user->hasPermission('timecards.view-all')) {
            return true;
        }

        return $timecard->user_id === $user->id
            && $timecard->status === Timecard::STATUS_DRAFT
            && $user->hasPermission('timecards.delete');
    }

    public function approve(User $user, Timecard $timecard): bool
    {
        return $timecard->status === Timecard::STATUS_SUBMITTED
            && $user->hasPermission('timecards.approve');
    }

    public function reject(User $user, Timecard $timecard): bool
    {
        return $timecard->status === Timecard::STATUS_SUBMITTED
            && $user->hasPermission('timecards.reject');
    }

    public function submit(User $user, Timecard $timecard): bool
    {
        return $timecard->user_id === $user->id
            && $timecard->status === Timecard::STATUS_DRAFT
            && $user->hasPermission('timecards.submit');
    }
}
