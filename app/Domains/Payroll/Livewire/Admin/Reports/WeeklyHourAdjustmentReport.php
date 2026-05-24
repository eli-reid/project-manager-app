<?php

namespace App\Domains\Payroll\Livewire\Admin\Reports;

use App\Core\Identity\Models\User;
use App\Domains\Payroll\Models\WeeklyEmployeeHoursAdjustment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Weekly Hour Adjustment Report')]
class WeeklyHourAdjustmentReport extends Component
{
    use AuthorizesRequests;

    #[Url(as: 'week_start')]
    public ?string $weekStart = null;

    #[Url(as: 'year')]
    public ?int $year = null;

    #[Url(as: 'employee')]
    public string $employeeId = 'all';

    public function mount(): void
    {
        $this->authorize('payroll-runs.preview');

        if ($this->weekStart) {
            $this->weekStart = CarbonImmutable::parse($this->weekStart)->toDateString();
        }

        $defaultYear = $this->weekStart
            ? CarbonImmutable::parse($this->weekStart)->year
            : today()->year;

        $this->year = $this->normalizeYear($this->year ?? $defaultYear);

        if ($this->employeeId === '') {
            $this->employeeId = 'all';
        }
    }

    private function normalizeYear(null|int|string $year): int
    {
        $normalized = filter_var($year, FILTER_VALIDATE_INT);

        if ($normalized === false || $normalized < 2000 || $normalized > 2100) {
            return (int) today()->year;
        }

        return (int) $normalized;
    }

    public function updatedYear(): void
    {
        $this->year = $this->normalizeYear($this->year);
    }

    public function updatedEmployeeId(): void
    {
        if ($this->employeeId === '') {
            $this->employeeId = 'all';
        }
    }

    public function getAvailableYearsProperty(): Collection
    {
        $years = WeeklyEmployeeHoursAdjustment::query()
            ->orderByDesc('week_start')
            ->pluck('week_start')
            ->map(static fn ($date): int => CarbonImmutable::parse($date)->year)
            ->unique()
            ->values();

        if (! $years->contains($this->year)) {
            $years->prepend($this->year);
        }

        return $years
            ->map(static fn (int $year): int => $year)
            ->sortDesc()
            ->values();
    }

    public function getEmployeesProperty(): Collection
    {
        $employeeIds = WeeklyEmployeeHoursAdjustment::query()
            ->whereYear('week_start', $this->year)
            ->distinct()
            ->pluck('user_id');

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $employeeIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);
    }

    public function getAdjustmentsProperty(): Collection
    {
        $query = WeeklyEmployeeHoursAdjustment::query()
            ->whereYear('week_start', $this->year)
            ->with(['employee:id,first_name,last_name', 'editor:id,first_name,last_name'])
            ->orderByDesc('week_start')
            ->orderByDesc('edited_at')
            ->orderByDesc('updated_at');

        if ($this->employeeId !== 'all') {
            $query->where('user_id', $this->employeeId);
        }

        return $query->get();
    }

    public function getTotalSourceHoursProperty(): float
    {
        return round(
            $this->adjustments->sum(
                static fn (WeeklyEmployeeHoursAdjustment $adjustment): float => (float) $adjustment->source_hours
            ),
            2
        );
    }

    public function getTotalAdjustedHoursProperty(): float
    {
        return round(
            $this->adjustments->sum(
                static fn (WeeklyEmployeeHoursAdjustment $adjustment): float => (float) $adjustment->adjusted_hours
            ),
            2
        );
    }

    public function getTotalDeltaProperty(): float
    {
        return round(
            $this->adjustments->sum(
                static fn (WeeklyEmployeeHoursAdjustment $adjustment): float => (float) $adjustment->adjusted_hours - (float) $adjustment->source_hours
            ),
            2
        );
    }

    public function render()
    {
        return view('payroll::livewire.admin.reports.weekly-hour-adjustments.index')
            ->layout($this->layoutName());
    }

    private function layoutName(): string
    {
        $referer = request()->headers->get('referer');
        $refererPath = is_string($referer) ? (string) parse_url($referer, PHP_URL_PATH) : '';

        return str_starts_with($refererPath, '/admin/payroll')
            ? 'payroll::livewire.layouts.payroll-admin'
            : 'layouts.app';
    }
}
