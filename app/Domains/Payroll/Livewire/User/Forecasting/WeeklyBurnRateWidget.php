<?php

namespace App\Domains\Payroll\Livewire\User\Forecasting;

use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Services\PayrollForecastingService;
use App\Domains\Projects\Models\Project;
use Livewire\Component;

class WeeklyBurnRateWidget extends Component
{
    public Project $project;

    public ?array $trailingForecast = null;

    public ?array $headcountForecast = null;

    public ?array $projectForecast = null;

    public ?string $projectPayRateTypeName = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->loadForecast();
    }

    public function loadForecast(): void
    {
        $service = app(PayrollForecastingService::class);

        $this->trailingForecast = $service->trailingAverageForecast();
        $this->headcountForecast = $service->headcountBasedForecast();
        $this->projectForecast = $service->projectBasedForecast((string) $this->project->id);

        $this->projectPayRateTypeName = null;

        if ($this->project->pay_rate_type_id) {
            $this->projectPayRateTypeName = PayRateType::query()
                ->whereKey($this->project->pay_rate_type_id)
                ->value('name');
        }
    }

    public function render()
    {
        return view('payroll::livewire.user.forecasting.weekly-burn-rate-widget');
    }
}
