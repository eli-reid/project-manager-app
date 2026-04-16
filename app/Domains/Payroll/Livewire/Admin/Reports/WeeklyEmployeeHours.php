<?php

namespace App\Domains\Payroll\Livewire\Admin\Reports;

use App\Core\Settings\Services\WeekSettingsService;
use App\Domains\Payroll\Contracts\PayrollTimecardReadGateway;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Weekly Employee Hours')]
class WeeklyEmployeeHours extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'week_start')]
    public ?string $weekStart = null;

    public function mount(): void
    {
        $this->authorize('payroll-runs.preview');

        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        if (! $this->weekStart) {
            $this->weekStart = today()->startOfWeek($weekStartsAt)->toDateString();
        }

        $this->weekStart = CarbonImmutable::parse($this->weekStart)
            ->startOfWeek($weekStartsAt)
            ->toDateString();
    }

    public function getWeekEndProperty(): CarbonImmutable
    {
        return app(WeekSettingsService::class)->weekEndFromStart($this->weekStart);
    }

    public function getEmployeeHoursProperty(): Collection
    {
        $weekStart = Carbon::parse($this->weekStart)->startOfDay();

        return app(PayrollTimecardReadGateway::class)
            ->weeklyEmployeeHoursForWeek($weekStart);
    }

    public function getTotalHoursProperty(): float
    {
        return round($this->employeeHours->sum('hours'), 2);
    }

    public function previousWeek(): void
    {
        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        $this->weekStart = CarbonImmutable::parse($this->weekStart)
            ->subWeek()
            ->startOfWeek($weekStartsAt)
            ->toDateString();
    }

    public function nextWeek(): void
    {
        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        $this->weekStart = CarbonImmutable::parse($this->weekStart)
            ->addWeek()
            ->startOfWeek($weekStartsAt)
            ->toDateString();
    }

    public function render()
    {
        return view('payroll::livewire.admin.reports.weekly-employee-hours.index');
    }
}
