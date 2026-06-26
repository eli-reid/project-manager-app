<?php

namespace App\Domains\RFIs\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;
use Illuminate\Validation\ValidationException;

class RFILifecycleService
{
    public function create(Project $project, User $requestedBy, array $attributes): RFI
    {
        $nextNumber = (int) RFI::query()
            ->where('project_id', $project->id)
            ->max('number') + 1;

        return RFI::create([
            'project_id' => $project->id,
            'number' => $nextNumber,
            'subject' => $attributes['subject'],
            'body' => $attributes['body'] ?? null,
            'status' => RFI::STATUS_DRAFT,
            'requested_by_id' => $requestedBy->id,
            'due_date' => $attributes['due_date'] ?? null,
        ]);
    }

    public function submit(RFI $rfi): RFI
    {
        if ($rfi->status !== RFI::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Only draft RFIs can be submitted.',
            ]);
        }

        $rfi->update(['status' => RFI::STATUS_SUBMITTED]);

        return $rfi;
    }

    public function answer(RFI $rfi, User $answeredBy, array $attributes): RFI
    {
        if ($rfi->status !== RFI::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted RFIs can be answered.',
            ]);
        }

        $rfi->update([
            'status' => RFI::STATUS_ANSWERED,
            'answer' => $attributes['answer'],
            'answered_by_id' => $answeredBy->id,
            'answered_at' => now(),
            'cost_impact' => $attributes['cost_impact'] ?? null,
            'schedule_impact_days' => $attributes['schedule_impact_days'] ?? null,
        ]);

        return $rfi;
    }

    public function close(RFI $rfi): RFI
    {
        if ($rfi->status !== RFI::STATUS_ANSWERED) {
            throw ValidationException::withMessages([
                'status' => 'Only answered RFIs can be closed.',
            ]);
        }

        $rfi->update(['status' => RFI::STATUS_CLOSED]);

        return $rfi;
    }

    public function cancel(RFI $rfi): RFI
    {
        if (in_array($rfi->status, [RFI::STATUS_CLOSED, RFI::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => 'This RFI cannot be cancelled.',
            ]);
        }

        $rfi->update(['status' => RFI::STATUS_CANCELLED]);

        return $rfi;
    }
}
