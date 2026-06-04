<?php

namespace App\Domains\Projects\Support\ProjectTabs;

use App\Domains\Dailies\Livewire\Admin\Dailies\Index as DailiesIndex;
use App\Domains\Dailies\Livewire\Admin\Dailies\Show as DailiesShow;
use App\Domains\Projects\Contracts\ProjectTabPanel;
use App\Domains\Projects\Models\Project;

class DailiesTabPanel implements ProjectTabPanel
{
    /**
     * @param  array<string, array{modeParam:string,mode:string,detailParam:?string,detailId:string,isCreateMode:bool}>  $tabContext
     * @param  array<string, mixed>  $viewState
     * @return array{component:string,props:array<string, mixed>,key:string}
     */
    public function resolve(string $tabKey, Project $project, array $tabContext, array $viewState = []): ?array
    {
        $dailyId = (string) ($tabContext[$tabKey]['detailId'] ?? '');

        if ($dailyId !== '') {
            return [
                'component' => DailiesShow::class,
                'props' => [
                    'dailyReport' => $dailyId,
                    'embedded' => true,
                    'returnTo' => route('admin.projects.show', ['project' => $project, 'tab' => $tabKey]),
                ],
                'key' => 'project-dailies-show-'.$project->id.'-'.$dailyId,
            ];
        }

        return [
            'component' => DailiesIndex::class,
            'props' => [
                'project' => $project,
                'embedded' => true,
            ],
            'key' => 'project-dailies-tab-'.$project->id,
        ];
    }
}
