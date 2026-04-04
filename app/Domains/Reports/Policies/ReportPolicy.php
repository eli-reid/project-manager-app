<?php

namespace App\Domains\Reports\Policies;

use App\Core\User\Models\User;

class ReportPolicy
{
    public function viewFinancial(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('financial-reports.view');
    }

    public function exportFinancial(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('financial-reports.export');
    }

    public function viewOperational(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('operational-reports.view');
    }

    public function exportOperational(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('operational-reports.export');
    }

    public function createOperationalTemplate(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('operational-reports.create-template');
    }

    public function scheduleOperational(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('operational-reports.schedule');
    }
}
