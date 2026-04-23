<?php

namespace App\Domains\Timecards\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;

class TimecardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('timecards.view') || $user->hasPermission('timecards.view-all');
    }

    public function viewAll(User $user): bool
    {
        return $user->hasPermission('timecards.view-all');
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
            return $timecard->status !== Timecard::STATUS_APPROVED;
        }

        return $timecard->user_id === $user->id
            && $timecard->status === Timecard::STATUS_DRAFT
            && $user->hasPermission('timecards.edit');
    }

    public function delete(User $user, Timecard $timecard): bool
    {
        if ($user->hasPermission('timecards.delete') && $user->hasPermission('timecards.view-all')) {
            return $timecard->status !== Timecard::STATUS_APPROVED;
        }

        return $timecard->user_id === $user->id
            && $timecard->status === Timecard::STATUS_DRAFT
            && $user->hasPermission('timecards.delete');
    }

    public function approve(User $user, Timecard $timecard): bool
    {
        if ($timecard->status === Timecard::STATUS_APPROVED) {
            return false;
        }

        // Admins who can view all timecards may approve draft timecards they created
        // without waiting for the employee to submit.
        if ($user->hasPermission('timecards.approve') && $user->hasPermission('timecards.view-all')) {
            return in_array($timecard->status, [Timecard::STATUS_SUBMITTED, Timecard::STATUS_DRAFT]);
        }

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

    public function reset(User $user, Timecard $timecard): bool
    {
        if ($user->hasPermission('timecards.edit') && $user->hasPermission('timecards.view-all')) {
            return $timecard->status === Timecard::STATUS_REJECTED;
        }

        return $timecard->user_id === $user->id
            && $timecard->status === Timecard::STATUS_REJECTED
            && $user->hasPermission('timecards.edit');
    }

    public function viewReports(User $user): bool
    {
        return $user->hasPermission('timecards.view-reports');
    }
}
