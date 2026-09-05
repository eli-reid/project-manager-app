<?php

namespace App\Domains\Projects\Support;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;

final class FinancialsProjectTab extends ProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'financials',
            label: 'Financials',
            sort: 120,
            panel: new LivewireComponentTabPanel(
                component: 'projects::admin.projects.financials-tab',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewFinancials', $project);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return null;
    }
}
