<?php

namespace App\Domains\Reports\Livewire\Admin\Reports;

use App\Domains\Reports\Services\ReportRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Reports')]
class Index extends Component
{
    use AuthorizesRequests;

    /**
     * @var array<int, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int,ability:string}>
     */
    public array $financialReports = [];

    /**
     * @var array<int, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int,ability:string}>
     */
    public array $operationalReports = [];

    public function mount(ReportRegistry $reportRegistry): void
    {
        abort_unless(
            Auth::user()?->can('reports.financial.view')
            || Auth::user()?->can('reports.operational.view')
            || Auth::user()?->can('payroll-runs.preview'),
            403,
        );

        $this->financialReports = $this->visibleReports($reportRegistry->forSection('financial'));
        $this->operationalReports = $this->visibleReports($reportRegistry->forSection('operational'));
    }

    public function render(): View
    {
        return view('reports::livewire.admin.reports.index');
    }

    /**
     * @param  array<int, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int,ability:string}>  $reports
     * @return array<int, array{key:string,section:string,title:string,description:string,route:string,badge_label:string,badge_color:string,sort:int,ability:string}>
     */
    private function visibleReports(array $reports): array
    {
        return collect($reports)
            ->filter(function (array $report): bool {
                if (! Route::has($report['route'])) {
                    return false;
                }

                if ($report['ability'] === '') {
                    return true;
                }

                return Auth::user()?->can($report['ability']) ?? false;
            })
            ->values()
            ->all();
    }
}
