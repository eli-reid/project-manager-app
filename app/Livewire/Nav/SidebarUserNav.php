<?php

namespace App\Livewire\Nav;

use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\View\View;
use Livewire\Component;

class SidebarUserNav extends Component
{
    public function render(): View
    {
        return view('livewire.nav.user.sidebar', $this->getNavPermissions());
    }

    /**
     * @return array<string, bool>
     */
    private function getNavPermissions(): array
    {
        $user = auth()->user();

        return [
            'canViewProjects' => $user?->can('viewAny', Project::class) ?? false,
            'canViewTimecards' => $user?->can('viewAny', Timecard::class) ?? false,
            'canViewDailies' => $user?->can('viewAny', DailyReport::class) ?? false,
            'canViewStock' => $user?->can('viewAny', StockOrder::class) ?? false,
            'canViewDocuments' => $user?->can('viewAny', Document::class) ?? false,
        ];
    }
}
