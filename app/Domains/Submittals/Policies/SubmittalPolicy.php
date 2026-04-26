<?php

namespace App\Domains\Submittals\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Submittals\Models\Submittal;

class SubmittalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('submittals.view-any');
    }

    public function view(User $user, Submittal $submittal): bool
    {
        if ($user->hasPermission('submittals.view-any')) {
            return true;
        }

        return $user->hasPermission('submittals.view')
            && (string) $submittal->submitted_by_id === (string) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('submittals.create');
    }

    public function update(User $user, Submittal $submittal): bool
    {
        if (! $submittal->isEditable()) {
            return false;
        }

        if ($user->hasPermission('submittals.update') && $user->hasPermission('submittals.view-any')) {
            return true;
        }

        return $user->hasPermission('submittals.update')
            && (string) $submittal->submitted_by_id === (string) $user->id;
    }

    public function submit(User $user, Submittal $submittal): bool
    {
        return $user->hasPermission('submittals.submit')
            && $submittal->isEditable()
            && (string) $submittal->submitted_by_id === (string) $user->id;
    }

    public function review(User $user, Submittal $submittal): bool
    {
        if (! in_array($submittal->statusValue(), [
            Submittal::STATUS_UNDER_REVIEW,
            Submittal::STATUS_ARCHITECT_REVIEW,
            Submittal::STATUS_OWNER_REVIEW,
        ], true)) {
            return false;
        }

        return $user->hasPermission('submittals.review')
            || $user->hasPermission('submittals.approve')
            || $user->hasPermission('submittals.reject');
    }

    public function approve(User $user, Submittal $submittal): bool
    {
        return $user->hasPermission('submittals.approve')
            && in_array($submittal->statusValue(), [
                Submittal::STATUS_UNDER_REVIEW,
                Submittal::STATUS_ARCHITECT_REVIEW,
                Submittal::STATUS_OWNER_REVIEW,
            ], true);
    }

    public function reject(User $user, Submittal $submittal): bool
    {
        return $user->hasPermission('submittals.reject')
            && in_array($submittal->statusValue(), [
                Submittal::STATUS_UNDER_REVIEW,
                Submittal::STATUS_ARCHITECT_REVIEW,
                Submittal::STATUS_OWNER_REVIEW,
            ], true);
    }

    public function distribute(User $user, Submittal $submittal): bool
    {
        return $user->hasPermission('submittals.distribute')
            && $submittal->statusValue() === Submittal::STATUS_APPROVED;
    }

    public function cancel(User $user, Submittal $submittal): bool
    {
        $cancellable = [
            Submittal::STATUS_DRAFT,
            Submittal::STATUS_UNDER_REVIEW,
            Submittal::STATUS_ARCHITECT_REVIEW,
            Submittal::STATUS_OWNER_REVIEW,
            Submittal::STATUS_REVISE,
        ];

        if (! in_array($submittal->statusValue(), $cancellable, true)) {
            return false;
        }

        if ($user->hasPermission('submittals.cancel') && $user->hasPermission('submittals.view-any')) {
            return true;
        }

        return $user->hasPermission('submittals.cancel')
            && (string) $submittal->submitted_by_id === (string) $user->id;
    }

    public function revise(User $user, Submittal $submittal): bool
    {
        return $user->hasPermission('submittals.revise')
            && $submittal->statusValue() === Submittal::STATUS_REJECTED;
    }
}
