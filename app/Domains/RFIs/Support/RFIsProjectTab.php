<?php

namespace App\Domains\RFIs\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Contracts\AbstractProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;
use App\Domains\RFIs\Models\RFI;

final readonly class RFIsProjectTab extends AbstractProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'rfis',
            label: 'RFIs',
            sort: 80,
            //modeParam: 'rfiMode',
            detailQueryParam: 'rfiId',
            panel: new LivewireComponentTabPanel(
                component: 'App\\Domains\\RFIs\\Livewire\\Admin\\RFIs\\Index',
                baseProps: ['embedded' => true],
                createModeProp: 'isCreateMode',
                appendCreateSuffix: true,
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->hasPermission('rfis.view-any')
            || $user->hasPermission('rfis.view')
            || $user->hasPermission('rfis.create');
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return RFI::query()
            ->where('project_id', $project->id)
            ->count();
    }
}
