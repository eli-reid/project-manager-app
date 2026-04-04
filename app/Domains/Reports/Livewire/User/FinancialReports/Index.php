<?php

namespace App\Domains\Reports\Livewire\User\FinancialReports;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Financial Reports')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('reports.financial.view');
    }

    public function render()
    {
        return view('reports::livewire.user.financial-reports.index', [
            'phaseOneReports' => [
                [
                    'key' => 'project-profitability',
                    'label' => 'Project Profitability',
                    'description' => 'Analyze revenue, labor, and material totals by project.',
                ],
                [
                    'key' => 'monthly-performance',
                    'label' => 'Monthly Financial Performance',
                    'description' => 'Track month-over-month financial performance trends.',
                ],
                [
                    'key' => 'labor-cost-analysis',
                    'label' => 'Labor Cost Analysis',
                    'description' => 'Review labor cost distribution by project and period.',
                ],
                [
                    'key' => 'material-cost-analysis',
                    'label' => 'Material Cost Analysis',
                    'description' => 'Review material and vendor cost distribution by project and period.',
                ],
            ],
        ]);
    }
}
