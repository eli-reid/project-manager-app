<?php

namespace App\Domains\Payroll\Policies;

use App\Core\Identity\Models\User;

class PayrollPolicy
{
    /**
     * Perform pre-authorization checks.
     * Admins bypass all checks.
     */
    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine if the user can view payroll data.
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('payroll.view');
    }

    /**
     * Determine if the user can view all payroll data (not just their own).
     */
    public function viewAll(User $user): bool
    {
        return $user->hasPermission('payroll.view-all');
    }

    /**
     * Determine if the user can process payroll.
     */
    public function process(User $user): bool
    {
        return $user->hasPermission('payroll.process');
    }

    /**
     * Determine if the user can approve payroll.
     */
    public function approve(User $user): bool
    {
        return $user->hasPermission('payroll.approve');
    }

    /**
     * Determine if the user can export payroll data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermission('payroll.export');
    }

    /**
     * Determine if the user can finalize a payroll period.
     */
    public function finalize(User $user): bool
    {
        return $user->hasPermission('payroll.finalize');
    }

    /**
     * Determine if the user can manage payroll settings.
     */
    public function manage(User $user): bool
    {
        return $user->hasPermission('payroll.manage');
    }
}
