<?php

namespace App\Domains\Invoices\Policies;

use App\Core\User\Models\User;
use App\Domains\Invoices\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('invoices.create');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.edit') && ! $invoice->isPaid();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.delete') && ! $invoice->isPaid();
    }

    public function verify(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.verify')
            && ($invoice->isPending() || $invoice->isDraft());
    }

    public function markAsPaid(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.mark-paid')
            && ($invoice->isVerified() || $invoice->isPending());
    }

    public function reject(User $user, Invoice $invoice): bool
    {
        return $user->hasPermission('invoices.reject') && ! $invoice->isPaid();
    }
}
