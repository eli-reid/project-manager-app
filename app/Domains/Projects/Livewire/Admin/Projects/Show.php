<?php

namespace App\Domains\Projects\Livewire\Admin\Projects;

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectTabRegistry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    use AuthorizesRequests;

    private ProjectTabRegistry $projectTabRegistry;

    public Project $project;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    public int $taskWidgetVersion = 0;

    public function boot(ProjectTabRegistry $projectTabRegistry): void
    {
        $this->projectTabRegistry = $projectTabRegistry;
    }

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;

        if (! in_array($this->activeTab, $this->tabs(), true)) {
            $this->activeTab = 'overview';
        }
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, $this->tabs(), true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    public function sortProjectTab(string $tabKey, int $position): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $visibleTabKeys = collect($this->projectTabRegistry->visibleTabItems($this->project, $user))
            ->pluck('key')
            ->values()
            ->all();

        if (! in_array($tabKey, $visibleTabKeys, true)) {
            return;
        }

        $currentIndex = array_search($tabKey, $visibleTabKeys, true);
        if (! is_int($currentIndex)) {
            return;
        }

        unset($visibleTabKeys[$currentIndex]);
        $visibleTabKeys = array_values($visibleTabKeys);

        $position = max(0, min($position, count($visibleTabKeys)));
        array_splice($visibleTabKeys, $position, 0, [$tabKey]);

        $this->projectTabRegistry->updateUserTabOrder($user, $this->project, $visibleTabKeys);
    }

    public function hideTab(string $tabKey): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $this->projectTabRegistry->setUserTabHidden($user, $this->project, $tabKey, true);

        if ($this->activeTab === $tabKey) {
            $this->activeTab = $this->tabs()[0] ?? 'overview';
        }
    }

    public function showTab(string $tabKey): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $this->projectTabRegistry->setUserTabHidden($user, $this->project, $tabKey, false);
    }

    #[On('project-tasks-updated')]
    public function refreshTaskMetrics(string $projectId): void
    {
        if ($projectId !== (string) $this->project->id) {
            return;
        }

        $this->taskWidgetVersion++;
    }

    /**
     * @return array<int, string>
     */
    protected function tabs(): array
    {
        $user = Auth::user();

        return $this->projectTabRegistry->visibleTabs($this->project, $user instanceof User ? $user : null);
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user !== null, 401);

        $tabs = $this->tabs();
        if (! in_array($this->activeTab, $tabs, true)) {
            $this->activeTab = $tabs[0] ?? 'overview';
        }

        $visibleTabItems = $this->projectTabRegistry->visibleTabItems($this->project, $user);
        $hiddenTabItems = $this->projectTabRegistry->hiddenTabItems($this->project, $user);
        $tabBadges = $this->projectTabRegistry->tabBadges($this->project, $user instanceof User ? $user : null);

        $tabContext = collect($visibleTabItems)
            ->mapWithKeys(static function (array $tabItem): array {
                $tabKey = $tabItem['key'];
                $modeParam = is_string($tabItem['mode_param'] ?? null) ? $tabItem['mode_param'] : null;
                $detailQueryParam = is_string($tabItem['detail_query_param'] ?? null) ? $tabItem['detail_query_param'] : null;
                $resolvedModeParam = $modeParam ?? str($tabKey)->replace('-', ' ')->singular()->camel()->append('Mode')->value();

                $mode = (string) request()->query($resolvedModeParam, '');

                $detailId = $detailQueryParam !== null
                    ? (string) request()->query($detailQueryParam, '')
                    : '';

                return [$tabKey => [
                    'modeParam' => $resolvedModeParam,
                    'mode' => $mode,
                    'detailParam' => $detailQueryParam,
                    'detailId' => $detailId,
                    'isCreateMode' => $mode === 'create',
                ]];
            })
            ->all();

        $tabPanels = $this->projectTabRegistry->tabPanels(
            $this->project,
            $user instanceof User ? $user : null,
            $tabContext,
            ['taskWidgetVersion' => $this->taskWidgetVersion],
        );

        return view('projects::livewire.admin.projects.show', [
            'tabs' => $tabs,
            'visibleTabItems' => $visibleTabItems,
            'hiddenTabItems' => $hiddenTabItems,
            'tabBadges' => $tabBadges,
            'tabContext' => $tabContext,
            'tabPanels' => $tabPanels,
            'projectAddress' => $this->project->loadMissing('address')->address,
        ])->title('Project Details');
    }
}
