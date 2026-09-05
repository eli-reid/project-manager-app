<?php

namespace App\Domains\PaymentReceipts\Policies;

use App\Core\Identity\Models\User;
use App\Domains\PaymentReceipts\Models\PaymentReceipt;

class PaymentReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payment-receipts.view');
    }

    public function view(User $user, PaymentReceipt $paymentReceipt): bool
    {
        return $user->hasPermission('payment-receipts.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payment-receipts.create');
    }

    public function delete(User $user, PaymentReceipt $paymentReceipt): bool
    {
        return $user->hasPermission('payment-receipts.delete');
    }
}
