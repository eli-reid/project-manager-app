<?php

namespace App\Domains\RFIs\Policies;

use App\Core\Identity\Models\User;
use App\Domains\RFIs\Models\RFI;

class RFIPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('rfis.view-any');
    }

    public function view(User $user, RFI $rfi): bool
    {
        if ($user->hasPermission('rfis.view-any')) {
            return true;
        }

        if ($user->hasPermission('rfis.view') || $user->hasPermission('rfis.create')) {
            return (string) $rfi->requested_by_id === (string) $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('rfis.create');
    }

    public function update(User $user, RFI $rfi): bool
    {
        if ($rfi->status !== RFI::STATUS_DRAFT) {
            return false;
        }

        if ($user->hasPermission('rfis.update') && $user->hasPermission('rfis.view-any')) {
            return true;
        }

        return $user->hasPermission('rfis.update')
            && (string) $rfi->requested_by_id === (string) $user->id;
    }

    public function answer(User $user, RFI $rfi): bool
    {
        return $user->hasPermission('rfis.answer')
            && $rfi->status === RFI::STATUS_SUBMITTED;
    }

    public function close(User $user, RFI $rfi): bool
    {
        return $user->hasPermission('rfis.close')
            && $rfi->status === RFI::STATUS_ANSWERED;
    }

    public function cancel(User $user, RFI $rfi): bool
    {
        if (in_array($rfi->status, [RFI::STATUS_CLOSED, RFI::STATUS_CANCELLED], true)) {
            return false;
        }

        return $user->hasPermission('rfis.update');
    }
}
