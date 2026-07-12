<?php

namespace App\Core\UI\Dashboard\Livewire\Panels;

use App\Core\UI\Dashboard\Livewire\Concerns\ResolvesDashboardSections;
use App\Core\UI\Dashboard\Services\DashboardWidgetRegistry;
use Livewire\Component;

class Overview extends Component
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
        $this->sections = $this->resolveDashboardSections($widgetRegistry, 'dashboard::livewire.panels.overview');
    }

    public function render()
    {
        return view('dashboard::livewire.panels.overview', [
            'sections' => $this->sections,
            'sectionLabels' => self::SECTION_LABELS,
            'showLabels' => count($this->sections) > 1,
        ]);
    }
}
