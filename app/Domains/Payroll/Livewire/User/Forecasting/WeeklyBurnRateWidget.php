<?php

namespace App\Domains\Payroll\Livewire\User\Forecasting;

use App\Domains\Payroll\Services\PayrollForecastingService;
use Livewire\Component;

class WeeklyBurnRateWidget extends Component
{
    public ?array $forecast = null;

    public function mount(): void
    {
        $this->loadForecast();
    }

    public function loadForecast(): void
    {
        $service = app(PayrollForecastingService::class);
        $this->forecast = $service->trailingAverageForecast();
    }

    public function render()
    {
        return view('payroll::livewire.user.forecasting.weekly-burn-rate-widget');
    }
}
