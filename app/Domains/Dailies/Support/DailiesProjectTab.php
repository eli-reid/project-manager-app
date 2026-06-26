<?php

namespace App\Domains\Dailies\Support;

use App\Core\Identity\Models\User;
use App\Domains\Dailies\Livewire\Admin\Dailies\Index as DailiesIndex;
use App\Domains\Dailies\Livewire\Admin\Dailies\Show as DailiesShow;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Contracts\AbstractProjectTab;
use App\Domains\Projects\Support\ProjectTabs\LivewireComponentTabPanel;

final readonly class DailiesProjectTab extends AbstractProjectTab
{
    public function __construct()
    {
        parent::__construct(
            key: 'dailies',
            label: 'Dailies',
            sort: 20,
            modeParam: 'mode',
            detailQueryParam: 'detail',
            panel: new LivewireComponentTabPanel(
                component: DailiesIndex::class,
                baseProps: ['embedded' => true],
                detailView: [
                    'component' => DailiesShow::class,
                    'baseProps' => ['embedded' => true],
                    'detailProp' => 'dailyReport',
                    'keyPattern' => 'project-dailies-show-{projectId}-{detailId}',
                    'viewStateProps' => ['returnTo' => 'returnTo'],
                ],
                keyPattern: 'project-dailies-index-{projectId}',
            ),
        );
    }

    public function isVisible(User $user, Project $project): bool
    {
        return $user->can('viewAll', DailyReport::class);
    }

    public function badgeCount(User $user, Project $project): ?int
    {
        return $project->dailyReports()->count();
    }
}
