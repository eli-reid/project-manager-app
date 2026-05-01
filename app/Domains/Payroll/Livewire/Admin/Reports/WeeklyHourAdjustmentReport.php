<?php

namespace App\Domains\Payroll\Livewire\Admin\Reports;

use App\Core\Settings\Services\WeekSettingsService;
use App\Domains\Payroll\Models\WeeklyEmployeeHoursAdjustment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Weekly Hour Adjustment Report')]
class WeeklyHourAdjustmentReport extends Component
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

    public function updatedWeekStart(): void
    {
        $weekStartsAt = app(WeekSettingsService::class)->weekStartsAt();

        $this->weekStart = CarbonImmutable::parse($this->weekStart)
            ->startOfWeek($weekStartsAt)
            ->toDateString();
    }

    public function getAdjustmentsProperty(): Collection
    {
        return WeeklyEmployeeHoursAdjustment::query()
            ->whereDate('week_start', $this->weekStart)
            ->with(['employee:id,first_name,last_name', 'editor:id,first_name,last_name'])
            ->orderByDesc('edited_at')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function getTotalDeltaProperty(): float
    {
        return round(
            $this->adjustments->sum(
                fn (WeeklyEmployeeHoursAdjustment $adjustment): float => (float) $adjustment->adjusted_hours - (float) $adjustment->source_hours
            ),
            2
        );
    }

    public function render()
    {
        return view('payroll::livewire.admin.reports.weekly-hour-adjustments.index');
    }
}
