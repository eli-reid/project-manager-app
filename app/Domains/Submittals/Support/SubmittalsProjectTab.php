<?php

namespace App\Domains\Submittals\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Contracts\AbstractProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;
use App\Domains\Submittals\Models\Submittal;

final readonly class SubmittalsProjectTab extends AbstractProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'submittals',
            label: 'Submittals',
            sort: 60,
            //modeParam: 'submittalMode',
            detailQueryParam: 'submittalId',
            panel: new LivewireComponentTabPanel(
                component: 'submittals::admin.submittals.index',
                baseProps: ['embedded' => true],
                //modeProp: 'mode',
                detailProp: 'submittalId',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAny', Submittal::class)
            || $user->can('create', Submittal::class);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return $user->can('viewAny', Submittal::class)
            ? Submittal::query()->where('project_id', $project->id)->count()
            : 0;
    }
}
