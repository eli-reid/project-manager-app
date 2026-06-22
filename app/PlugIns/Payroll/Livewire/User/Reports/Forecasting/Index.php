<?php

namespace App\Domains\Payroll\Livewire\User\Reports\Forecasting;

use App\Domains\Payroll\Services\PayrollForecastingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Payroll Forecasting')]
class Index extends Component
{
    use AuthorizesRequests;

    public ?array $summary = null;

    public string $trailingWeeks = '4';

    public bool $includeOvertime = true;

    public function mount(): void
    {
        $this->authorize('reports.payroll.view');
        $this->loadForecast();
    }

    public function loadForecast(): void
    {
        $service = app(PayrollForecastingService::class);
        $this->summary = $service->getForecastSummary(
            trailingWeeks: (int) $this->trailingWeeks,
            includeOvertimeInCost: $this->includeOvertime,
        );
    }

    public function updatedTrailingWeeks(): void
    {
        $this->loadForecast();
    }

    public function updatedIncludeOvertime(): void
    {
        $this->loadForecast();
    }

    public function render(): View
    {
        return view('payroll::livewire.user.reports.forecasting.index');
    }
}
