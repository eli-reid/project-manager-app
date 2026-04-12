<?php

namespace App\Domains\Payroll\Policies;

use App\Core\Identity\Models\User;

class PayrollReportPolicy
{
    public function viewOwn(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('payroll.view');
    }

    public function viewReports(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('payroll-reports.view');
    }

    public function exportReports(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('payroll-reports.export');
    }

    public function generateReports(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('payroll-reports.generate');
    }

    public function certifyReports(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('payroll-reports.certify');
    }

    public function remitReports(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('payroll-reports.remit');
    }

    public function manageReports(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('payroll-reports.manage');
    }
}
