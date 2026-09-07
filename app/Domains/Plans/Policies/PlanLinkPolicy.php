<?php

namespace App\Domains\Plans\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanLink;

class PlanLinkPolicy
{
    public function create(User $user, PlanLink $link): bool
    {
        return $user->can('view', $link->fromRevision->sheet) && $user->hasPermission('plans.manage-links');
    }

    public function update(User $user, PlanLink $link): bool
    {
        return $this->create($user, $link);
    }

    public function delete(User $user, PlanLink $link): bool
    {
        return $this->create($user, $link);
    }

    public function confirm(User $user, PlanLink $link): bool
    {
        return $this->create($user, $link);
    }
}
