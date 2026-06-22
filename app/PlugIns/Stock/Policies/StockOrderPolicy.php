<?php

namespace App\Domains\Stock\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Stock\Models\StockOrder;

class StockOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('stock-orders.view-any');
    }

    public function view(User $user, StockOrder $stockOrder): bool
    {
        if ($user->hasPermission('stock-orders.view-any')) {
            return true;
        }

        return $user->hasPermission('stock-orders.view')
            && (string) $stockOrder->user_id === (string) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('stock-orders.create');
    }

    public function update(User $user, StockOrder $stockOrder): bool
    {
        if (! $stockOrder->isMutable()) {
            return false;
        }

        if ($user->hasPermission('stock-orders.update') && $user->hasPermission('stock-orders.view-any')) {
            return true;
        }

        return $user->hasPermission('stock-orders.update')
            && (string) $stockOrder->user_id === (string) $user->id;
    }

    public function delete(User $user, StockOrder $stockOrder): bool
    {
        if (! $stockOrder->isMutable()) {
            return false;
        }

        if ($user->hasPermission('stock-orders.delete') && $user->hasPermission('stock-orders.view-any')) {
            return true;
        }

        return $user->hasPermission('stock-orders.delete')
            && (string) $stockOrder->user_id === (string) $user->id;
    }

    public function process(User $user, StockOrder $stockOrder): bool
    {
        return $user->hasPermission('stock-orders.process')
            && (
                $stockOrder->canTransitionTo(StockOrder::STATUS_APPROVED)
                || $stockOrder->canTransitionTo(StockOrder::STATUS_ORDERED)
                || $stockOrder->canTransitionTo(StockOrder::STATUS_RECEIVED)
                || $stockOrder->canTransitionTo(StockOrder::STATUS_CANCELLED)
            );
    }
}
