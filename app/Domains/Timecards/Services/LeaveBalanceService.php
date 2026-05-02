<?php

namespace App\Domains\Timecards\Services;

use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class LeaveBalanceService
{
    /**
     * @return array{
     *   sick: array{allowed: float, used: float, remaining: float},
     *   vacation: array{allowed: float, used: float, remaining: float}
     * }
     */
    public function forUser(User $user): array
    {
        $profile = $user->payrollProfile()
            ->select(['sick_hours_allowance', 'vacation_hours_allowance', 'hire_date'])
            ->first();

        [$periodStart, $periodEnd] = $this->resolveResetWindow($profile?->hire_date);

        $allowed = [
            'sick' => (float) ($profile?->sick_hours_allowance ?? 0),
            'vacation' => (float) ($profile?->vacation_hours_allowance ?? 0),
        ];

        $used = [
            'sick' => 0.0,
            'vacation' => 0.0,
        ];

        $rows = TimecardEntry::query()
            ->selectRaw('projects.leave_category as leave_category, COALESCE(SUM(timecard_entries.hours), 0) as hours_used')
            ->join('projects', 'projects.id', '=', 'timecard_entries.project_id')
            ->join('timecards', 'timecards.id', '=', 'timecard_entries.timecard_id')
            ->where('timecard_entries.user_id', $user->id)
            ->where('timecards.status', Timecard::STATUS_APPROVED)
            ->whereIn('projects.leave_category', ['sick', 'vacation'])
            ->whereDate('timecard_entries.date', '>=', $periodStart->toDateString())
            ->whereDate('timecard_entries.date', '<=', $periodEnd->toDateString())
            ->groupBy('projects.leave_category')
            ->get();

        foreach ($rows as $row) {
            $category = (string) ($row->leave_category ?? '');

            if (! array_key_exists($category, $used)) {
                continue;
            }

            $used[$category] = (float) ($row->hours_used ?? 0);
        }

        return [
            'sick' => [
                'allowed' => $allowed['sick'],
                'used' => $used['sick'],
                'remaining' => $allowed['sick'] - $used['sick'],
            ],
            'vacation' => [
                'allowed' => $allowed['vacation'],
                'used' => $used['vacation'],
                'remaining' => $allowed['vacation'] - $used['vacation'],
            ],
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveResetWindow(?DateTimeInterface $hireDate): array
    {
        $today = now()->startOfDay();
        $policy = strtolower(trim((string) Settings::get('payroll.leave.reset_policy', 'calendar_year')->raw()));

        if ($policy === 'hire_date' && $hireDate !== null) {
            $start = $this->hireDateCycleStart($today, Carbon::instance($hireDate)->startOfDay());

            return [$start, $today];
        }

        return [$today->copy()->startOfYear(), $today];
    }

    private function hireDateCycleStart(CarbonInterface $today, CarbonInterface $hireDate): Carbon
    {
        $anniversaryThisYear = $this->anniversaryForYear($hireDate, (int) $today->year);

        if ($anniversaryThisYear->greaterThan($today)) {
            return $this->anniversaryForYear($hireDate, (int) $today->year - 1);
        }

        return $anniversaryThisYear;
    }

    private function anniversaryForYear(CarbonInterface $hireDate, int $year): Carbon
    {
        $month = (int) $hireDate->month;
        $day = (int) $hireDate->day;
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;

        return Carbon::create($year, $month, min($day, $daysInMonth))->startOfDay();
    }
}
