<?php

namespace App\Domains\Dailies\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Dailies\Models\DailyReport;

class DailyReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('dailies.view') || $user->hasPermission('dailies.view-all');
    }

    public function viewAll(User $user): bool
    {
        return $user->hasPermission('dailies.view-all');
    }

    public function view(User $user, DailyReport $dailyReport): bool
    {
        if ($user->hasPermission('dailies.view-all')) {
            return true;
        }

        return $dailyReport->user_id === $user->id && $user->hasPermission('dailies.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('dailies.create');
    }

    public function update(User $user, DailyReport $dailyReport): bool
    {
        if ($user->hasPermission('dailies.edit') && $user->hasPermission('dailies.view-all')) {
            return $dailyReport->status !== DailyReport::STATUS_APPROVED;
        }

        return $dailyReport->user_id === $user->id
            && in_array($dailyReport->status, [DailyReport::STATUS_DRAFT, DailyReport::STATUS_REJECTED], true)
            && $user->hasPermission('dailies.edit');
    }

    public function delete(User $user, DailyReport $dailyReport): bool
    {
        if ($user->hasPermission('dailies.delete') && $user->hasPermission('dailies.view-all')) {
            return $dailyReport->status !== DailyReport::STATUS_APPROVED;
        }

        return $dailyReport->user_id === $user->id
            && $dailyReport->status === DailyReport::STATUS_DRAFT
            && $user->hasPermission('dailies.delete');
    }

    public function submit(User $user, DailyReport $dailyReport): bool
    {
        return $dailyReport->user_id === $user->id
            && $dailyReport->status === DailyReport::STATUS_DRAFT
            && $user->hasPermission('dailies.submit');
    }

    public function approve(User $user, DailyReport $dailyReport): bool
    {
        return $dailyReport->status === DailyReport::STATUS_SUBMITTED
            && $user->hasPermission('dailies.approve');
    }

    public function reject(User $user, DailyReport $dailyReport): bool
    {
        return $dailyReport->status === DailyReport::STATUS_SUBMITTED
            && $user->hasPermission('dailies.reject');
    }

    public function viewReports(User $user): bool
    {
        return $user->hasPermission('dailies.view-reports');
    }
}
