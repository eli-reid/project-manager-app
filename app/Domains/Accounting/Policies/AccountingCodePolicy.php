<?php

namespace App\Domains\Accounting\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Accounting\Models\AccountingCode;

class AccountingCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('accounting-codes.view');
    }

    public function view(User $user, AccountingCode $accountingCode): bool
    {
        return $user->hasPermission('accounting-codes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('accounting-codes.create');
    }

    public function update(User $user, AccountingCode $accountingCode): bool
    {
        return $user->hasPermission('accounting-codes.edit');
    }

    public function delete(User $user, AccountingCode $accountingCode): bool
    {
        return $user->hasPermission('accounting-codes.delete');
    }
}
