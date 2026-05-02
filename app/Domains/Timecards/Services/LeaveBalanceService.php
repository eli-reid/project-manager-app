<?php

namespace App\Domains\Timecards\Services;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;

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
            ->select(['sick_hours_allowance', 'vacation_hours_allowance'])
            ->first();

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
}
