<?php

namespace App\Domains\Payroll\Livewire\Admin\Reports;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
        $this->authorize('viewAny', Timecard::class);
        
        if (!$this->weekStart) {
            $this->weekStart = today()->startOfWeek()->toDateString();
        }
    }

    public function getWeekEndProperty(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->weekStart)->endOfWeek();
    }

    public function getEmployeeHoursProperty(): Collection
    {
        $weekStart = CarbonImmutable::parse($this->weekStart);
        $weekEnd = $this->getWeekEndProperty();

        return User::query()
            ->whereHas('timecards', function ($query) use ($weekStart, $weekEnd) {
                $query->whereBetween('week_starting', [$weekStart, $weekStart])
                    ->whereIn('status', [Timecard::STATUS_SUBMITTED, Timecard::STATUS_APPROVED]);
            })
            ->with([
                'timecards' => function ($query) use ($weekStart) {
                    $query->where('week_starting', $weekStart)
                        ->whereIn('status', [Timecard::STATUS_SUBMITTED, Timecard::STATUS_APPROVED])
                        ->with('entries');
                },
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(function (User $user) {
                $totalHours = $user->timecards
                    ->flatMap(fn ($tc) => $tc->entries)
                    ->sum('hours');

                return [
                    'user' => $user,
                    'hours' => round($totalHours, 2),
                ];
            });
    }

    public function getTotalHoursProperty(): float
    {
        return round($this->employeeHours->sum('hours'), 2);
    }

    public function previousWeek(): void
    {
        $this->weekStart = CarbonImmutable::parse($this->weekStart)->subWeek()->startOfWeek()->toDateString();
    }

    public function nextWeek(): void
    {
        $this->weekStart = CarbonImmutable::parse($this->weekStart)->addWeek()->startOfWeek()->toDateString();
    }

    public function render()
    {
        return view('payroll::livewire.admin.reports.weekly-employee-hours.index');
    }
}
