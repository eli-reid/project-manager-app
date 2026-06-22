<?php

namespace App\Domains\ChangeOrders\Policies;

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;

class ChangeOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('change-orders.view');
    }

    public function view(User $user, ChangeOrder $changeOrder): bool
    {
        return $user->hasPermission('change-orders.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('change-orders.create');
    }

    public function update(User $user, ChangeOrder $changeOrder): bool
    {
        return $user->hasPermission('change-orders.edit')
            && in_array($changeOrder->status, [ChangeOrder::STATUS_DRAFT, ChangeOrder::STATUS_REJECTED], true);
    }

    public function submit(User $user, ChangeOrder $changeOrder): bool
    {
        return $user->hasPermission('change-orders.edit')
            && in_array($changeOrder->status, [ChangeOrder::STATUS_DRAFT, ChangeOrder::STATUS_REJECTED], true);
    }

    public function approve(User $user, ChangeOrder $changeOrder): bool
    {
        return $user->hasPermission('change-orders.approve')
            && $changeOrder->status === ChangeOrder::STATUS_SUBMITTED;
    }

    public function reject(User $user, ChangeOrder $changeOrder): bool
    {
        return $user->hasPermission('change-orders.approve')
            && $changeOrder->status === ChangeOrder::STATUS_SUBMITTED;
    }

    public function implement(User $user, ChangeOrder $changeOrder): bool
    {
        return $user->hasPermission('change-orders.edit')
            && $changeOrder->status === ChangeOrder::STATUS_APPROVED;
    }

    public function cancel(User $user, ChangeOrder $changeOrder): bool
    {
        return $user->hasPermission('change-orders.edit')
            && in_array($changeOrder->status, [
                ChangeOrder::STATUS_DRAFT,
                ChangeOrder::STATUS_SUBMITTED,
                ChangeOrder::STATUS_APPROVED,
                ChangeOrder::STATUS_REJECTED,
            ], true);
    }
}
