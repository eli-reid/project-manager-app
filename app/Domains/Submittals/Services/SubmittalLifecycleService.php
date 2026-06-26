<?php

namespace App\Domains\Submittals\Services;

use App\Core\Identity\Models\User;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Models\SubmittalApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmittalLifecycleService
{
    public function submit(Submittal $submittal): Submittal
    {
        if (! $submittal->isEditable()) {
            throw ValidationException::withMessages([
                'submittal' => 'Only draft or revise submittals may be submitted.',
            ]);
        }

        $firstPendingApproval = $submittal->approvals()
            ->where('status', SubmittalApproval::STATUS_PENDING)
            ->orderBy('step')
            ->first();

        if (! $firstPendingApproval instanceof SubmittalApproval) {
            throw ValidationException::withMessages([
                'submittal' => 'Assign at least one reviewer before submitting.',
            ]);
        }

        $submittal->update([
            'status' => Submittal::STATUS_UNDER_REVIEW,
            'submitted_at' => now(),
            'approved_at' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'cancelled_at' => null,
            'distributed_at' => null,
            'current_reviewer_id' => $firstPendingApproval->reviewer_id,
        ]);

        return $submittal->fresh();
    }

    public function assignReviewers(Submittal $submittal, array $reviewerIds): Submittal
    {
        DB::transaction(function () use ($submittal, $reviewerIds): void {
            $submittal->approvals()->delete();

            foreach (array_values($reviewerIds) as $index => $reviewerId) {
                SubmittalApproval::query()->create([
                    'submittal_id' => $submittal->id,
                    'step' => $index + 1,
                    'reviewer_id' => $reviewerId,
                    'status' => SubmittalApproval::STATUS_PENDING,
                ]);
            }

            $submittal->update([
                'current_reviewer_id' => $reviewerIds[0] ?? null,
                'status' => Submittal::STATUS_UNDER_REVIEW,
            ]);
        });

        return $submittal->fresh('approvals');
    }

    public function approve(Submittal $submittal, User $reviewer, ?string $comment = null): Submittal
    {
        return DB::transaction(function () use ($submittal, $reviewer, $comment): Submittal {
            $approval = $submittal->approvals()
                ->where('reviewer_id', $reviewer->id)
                ->where('status', SubmittalApproval::STATUS_PENDING)
                ->orderBy('step')
                ->first();

            if (! $approval instanceof SubmittalApproval) {
                throw ValidationException::withMessages([
                    'submittal' => 'No pending review step found for this reviewer.',
                ]);
            }

            $approval->update([
                'status' => SubmittalApproval::STATUS_APPROVED,
                'reviewed_at' => now(),
                'comments' => $comment,
            ]);

            $nextApproval = $submittal->approvals()
                ->where('status', SubmittalApproval::STATUS_PENDING)
                ->orderBy('step')
                ->first();

            if ($nextApproval instanceof SubmittalApproval) {
                $submittal->update([
                    'current_reviewer_id' => $nextApproval->reviewer_id,
                    'status' => match ((int) $nextApproval->step) {
                        1 => Submittal::STATUS_UNDER_REVIEW,
                        2 => Submittal::STATUS_ARCHITECT_REVIEW,
                        default => Submittal::STATUS_OWNER_REVIEW,
                    },
                ]);
            } else {
                $submittal->update([
                    'status' => Submittal::STATUS_APPROVED,
                    'current_reviewer_id' => null,
                    'approved_at' => now(),
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            }

            return $submittal->fresh(['approvals']);
        });
    }

    public function reject(Submittal $submittal, User $reviewer, string $reason): Submittal
    {
        return DB::transaction(function () use ($submittal, $reviewer, $reason): Submittal {
            $approval = $submittal->approvals()
                ->where('reviewer_id', $reviewer->id)
                ->where('status', SubmittalApproval::STATUS_PENDING)
                ->orderBy('step')
                ->first();

            if (! $approval instanceof SubmittalApproval) {
                throw ValidationException::withMessages([
                    'submittal' => 'No pending review step found for this reviewer.',
                ]);
            }

            $approval->update([
                'status' => SubmittalApproval::STATUS_REJECTED,
                'reviewed_at' => now(),
                'comments' => $reason,
            ]);

            $submittal->update([
                'status' => Submittal::STATUS_REJECTED,
                'current_reviewer_id' => null,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'approved_at' => null,
            ]);

            return $submittal->fresh(['approvals']);
        });
    }

    public function distribute(Submittal $submittal): Submittal
    {
        if ($submittal->statusValue() !== Submittal::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'submittal' => 'Only approved submittals may be distributed.',
            ]);
        }

        $submittal->update([
            'status' => Submittal::STATUS_DISTRIBUTED,
            'distributed_at' => now(),
        ]);

        return $submittal->fresh();
    }

    public function cancel(Submittal $submittal): Submittal
    {
        $cancellable = [
            Submittal::STATUS_DRAFT,
            Submittal::STATUS_UNDER_REVIEW,
            Submittal::STATUS_ARCHITECT_REVIEW,
            Submittal::STATUS_OWNER_REVIEW,
            Submittal::STATUS_REVISE,
        ];

        if (! in_array($submittal->statusValue(), $cancellable, true)) {
            throw ValidationException::withMessages([
                'submittal' => 'This submittal cannot be cancelled in its current state.',
            ]);
        }

        $submittal->update([
            'status' => Submittal::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $submittal->fresh();
    }

    public function revise(Submittal $submittal): Submittal
    {
        if ($submittal->statusValue() !== Submittal::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'submittal' => 'Only rejected submittals may be marked for revision.',
            ]);
        }

        $submittal->update([
            'status' => Submittal::STATUS_REVISE,
            'rejected_at' => null,
            'rejection_reason' => null,
            'current_reviewer_id' => null,
        ]);

        $submittal->approvals()->update(['status' => SubmittalApproval::STATUS_PENDING, 'reviewed_at' => null, 'comments' => null]);

        return $submittal->fresh();
    }
}
