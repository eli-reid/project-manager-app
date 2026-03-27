<?php

namespace App\Domains\Stock\Policies;

use App\Core\User\Models\User;
use App\Domains\Stock\Models\StockOrderTemplate;

class StockOrderTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('stock-order-templates.view-any');
    }

    public function view(User $user, StockOrderTemplate $stockOrderTemplate): bool
    {
        if ($user->hasPermission('stock-order-templates.view-any')) {
            return $stockOrderTemplate->is_active;
        }

        return $user->hasPermission('stock-order-templates.view')
            && $stockOrderTemplate->isAvailableTo((string) $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('stock-order-templates.create');
    }

    public function update(User $user, StockOrderTemplate $stockOrderTemplate): bool
    {
        if (! $stockOrderTemplate->is_active) {
            return false;
        }

        if ($user->hasPermission('stock-order-templates.view-any')) {
            return $user->hasPermission('stock-order-templates.update');
        }

        return $user->hasPermission('stock-order-templates.update')
            && ! $stockOrderTemplate->is_global
            && (string) $stockOrderTemplate->created_by === (string) $user->id;
    }

    public function delete(User $user, StockOrderTemplate $stockOrderTemplate): bool
    {
        if (! $stockOrderTemplate->is_active) {
            return false;
        }

        if ($user->hasPermission('stock-order-templates.view-any')) {
            return $user->hasPermission('stock-order-templates.delete');
        }

        return $user->hasPermission('stock-order-templates.delete')
            && ! $stockOrderTemplate->is_global
            && (string) $stockOrderTemplate->created_by === (string) $user->id;
    }
}
