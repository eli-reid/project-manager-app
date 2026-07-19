<?php

namespace App\Core\UI\Dashboard\Livewire;

use App\Core\Identity\Livewire\Layouts\AccessAdmin;
use App\Core\Settings\Facades\Settings;
use App\Core\UI\Dashboard\Services\DashboardPanelRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Layout('layouts.dashboard')]
#[Title('Dashboard')]
class Index extends Component
{
    /**
     * @var array<int, array{key: string, component: string, icon: string, sort: int, ability: string, ability_model: string, label: string, description: string, badge: string}>
     */
    public array $panels = [];

    #[Url(as: 'panel')]
    public string $activePanel = 'overview';

    public function mount(DashboardPanelRegistry $panelRegistry): void
    {
        $this->panels = $this->resolvePanels($panelRegistry);

        if ($this->panels === []) {
            return;
        }

        if (! collect($this->panels)->contains(fn (array $panel): bool => $panel['key'] === $this->activePanel)) {
            $this->activePanel = $this->panels[0]['key'];
        }
    }

    public function render()
    {
        $currentPanel = collect($this->panels)->firstWhere('key', $this->activePanel);
        $user = Auth::user();
        $displayName = trim((string) ($user?->name ?? 'Dashboard User'));
        $initials = collect(explode(' ', $displayName))
            ->filter()
            ->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $siteName = Settings::get('site_name', config('app.name'));

        return view('dashboard::livewire.index', [
            'panels' => $this->panels,
            'activePanel' => $this->activePanel,
            'currentPanel' => $currentPanel,
            'currentPanelNavbarItems' => $this->resolveCurrentPanelNavbarItems(),
            'displayName' => $displayName,
            'displayInitials' => $initials !== '' ? $initials : 'DU',
            'siteName' => $siteName,
        ]);
    }

    /**
     * @return array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    private function resolveCurrentPanelNavbarItems(): array
    {
        if (! in_array($this->activePanel, ['access-users', 'access-roles', 'access-email-management'], true)) {
            return [];
        }

        return AccessAdmin::navbarItems();
    }

    /**
     * @return array<int, array{key: string, component: string, icon: string, sort: int, ability: string, ability_model: string, label: string, description: string, badge: string}>
     */
    private function resolvePanels(DashboardPanelRegistry $panelRegistry): array
    {
        return collect($panelRegistry->all())
            ->filter(function (array $panel): bool {
                if (($panel['ability'] ?? '') === '') {
                    return true;
                }

                try {
                    $model = $panel['ability_model'] ?? '';

                    return $model !== ''
                        ? Gate::allows($panel['ability'], $model)
                        : Gate::allows($panel['ability']);
                } catch (Throwable) {
                    return false;
                }
            })
            ->values()
            ->all();
    }
}
