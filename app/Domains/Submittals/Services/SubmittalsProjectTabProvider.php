<?php

namespace App\Domains\Submittals\Services;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Projects\Support\ProjectTab;

class SubmittalsProjectTabProvider
{
    public function definitions(): array
    {
        return [
            new ProjectTab(
                key: 'submittals',
                label: 'Submittals',
                sort: 60,
                modeParam: 'submittalMode',
                detailQueryParam: 'submittalId',
                panel: null,
                badgeResolver: static fn (User $user, Project $project): ?int => $user->can('viewAny', Submittal::class)
                    ? Submittal::query()->where('project_id', $project->id)->count()
                    : 0,
                visibilityResolver: static fn (User $user, Project $project): bool => $user->can('viewAny', Submittal::class)
                    || $user->can('create', Submittal::class),
            ),
        ];
    }
}
