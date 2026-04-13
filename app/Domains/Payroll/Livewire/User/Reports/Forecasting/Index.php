<?php

namespace App\Domains\Payroll\Livewire\User\Reports\Forecasting;

use App\Domains\Payroll\Services\PayrollForecastingService;
use Livewire\Component;

class Index extends Component
{
    public ?array $summary = null;

    public string $trailingWeeks = '4';

    public bool $includeOvertime = true;

    public function mount(): void
    {
        $this->loadForecast();
    }

    public function loadForecast(): void
    {
        $service = app(PayrollForecastingService::class);
        $this->summary = $service->getForecastSummary();
    }

    public function updateTrailingWeeks(): void
    {
        $this->loadForecast();
    }

    public function updateIncludeOvertime(): void
    {
        $this->loadForecast();
    }

    public function render()
    {
        return view('payroll::livewire.user.reports.forecasting.index');
    }
}
