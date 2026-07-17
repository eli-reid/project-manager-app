<?php

namespace App\Core\UI\Dashboard\Livewire;

use App\Core\UI\Dashboard\Livewire\Concerns\ResolvesDashboardSections;
use App\Core\UI\Dashboard\Services\DashboardWidgetRegistry;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.mobile')]
#[Title('Dashboard')]
class MobileIndex extends Component
{
    use ResolvesDashboardSections;

    /**
     * @var array<string, string>
     */
    private const SECTION_LABELS = [
        'primary' => 'General',
        'personal' => 'My Work',
        'operations' => 'Operations',
        'alerts' => 'Alerts',
        'admin' => 'Administration',
    ];

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    public array $sections = [];

    public function mount(DashboardWidgetRegistry $widgetRegistry): void
    {
        $this->sections = $this->resolveDashboardSections($widgetRegistry, 'dashboard::livewire.mobile.index');
    }

    public function render()
    {
        $showLabels = count($this->sections) > 1;

        return view('dashboard::livewire.mobile.index', [
            'sections' => $this->sections,
            'sectionLabels' => self::SECTION_LABELS,
            'showLabels' => $showLabels,
        ]);
    }
}
