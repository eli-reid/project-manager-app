<?php

namespace App\Domains\ChangeOrders\Services;

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use Illuminate\Validation\ValidationException;

class ChangeOrderLifecycleService
{
    public function submit(ChangeOrder $changeOrder): ChangeOrder
    {
        if (! in_array($changeOrder->status, [ChangeOrder::STATUS_DRAFT, ChangeOrder::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages([
                'change_order' => 'Only draft or rejected change orders may be submitted.',
            ]);
        }

        $changeOrder->recalculateTotal()->save();

        $changeOrder->update([
            'status' => ChangeOrder::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'approved_by_id' => null,
            'approved_at' => null,
            'rejected_by_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'implemented_at' => null,
            'cancelled_at' => null,
        ]);

        return $changeOrder->fresh();
    }

    public function approve(ChangeOrder $changeOrder, User $approver): ChangeOrder
    {
        if ($changeOrder->status !== ChangeOrder::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'change_order' => 'Only submitted change orders may be approved.',
            ]);
        }

        $changeOrder->update([
            'status' => ChangeOrder::STATUS_APPROVED,
            'approved_by_id' => $approver->id,
            'approved_at' => now(),
            'rejected_by_id' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'cancelled_at' => null,
        ]);

        return $changeOrder->fresh();
    }

    public function reject(ChangeOrder $changeOrder, User $rejector, ?string $reason = null): ChangeOrder
    {
        if ($changeOrder->status !== ChangeOrder::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'change_order' => 'Only submitted change orders may be rejected.',
            ]);
        }

        $changeOrder->update([
            'status' => ChangeOrder::STATUS_REJECTED,
            'rejected_by_id' => $rejector->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'approved_by_id' => null,
            'approved_at' => null,
            'cancelled_at' => null,
        ]);

        return $changeOrder->fresh();
    }

    public function implement(ChangeOrder $changeOrder): ChangeOrder
    {
        if ($changeOrder->status !== ChangeOrder::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'change_order' => 'Only approved change orders may be implemented.',
            ]);
        }

        $changeOrder->update([
            'status' => ChangeOrder::STATUS_IMPLEMENTED,
            'implemented_at' => now(),
            'cancelled_at' => null,
        ]);

        return $changeOrder->fresh();
    }

    public function cancel(ChangeOrder $changeOrder): ChangeOrder
    {
        if (in_array($changeOrder->status, [ChangeOrder::STATUS_IMPLEMENTED, ChangeOrder::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'change_order' => 'Implemented or cancelled change orders may not be cancelled.',
            ]);
        }

        $changeOrder->update([
            'status' => ChangeOrder::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $changeOrder->fresh();
    }
}
