<?php

namespace App\Domains\Plans\Policies;

use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanAnnotation;

class PlanAnnotationPolicy
{
    public function viewAny(User $user, PlanAnnotation $annotation): bool
    {
        return $user->can('view', $annotation->sheet);
    }

    public function create(User $user, PlanAnnotation $annotation): bool
    {
        return $user->can('view', $annotation->sheet) && $user->hasPermission('plans.annotate');
    }

    public function update(User $user, PlanAnnotation $annotation): bool
    {
        return $user->can('view', $annotation->sheet)
            && ($annotation->author_id === $user->id ? $user->hasPermission('plans.annotate') : $user->hasPermission('plans.manage-annotations'));
    }

    public function delete(User $user, PlanAnnotation $annotation): bool
    {
        return $this->update($user, $annotation);
    }

    public function resolve(User $user, PlanAnnotation $annotation): bool
    {
        return $user->can('update', $annotation);
    }
}
