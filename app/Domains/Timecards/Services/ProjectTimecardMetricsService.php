<?php

namespace App\Domains\Timecards\Services;

use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ProjectTimecardMetricsService
{
    /**
     * @return array{
     *     time_entry_count: int,
     *     total_hours: float,
     *     regular_hours: float,
     *     overtime_hours: float,
     *     double_time_hours: float,
     * }
     */
    public function summaryForProject(string $projectId): array
    {
        $baseQuery = TimecardEntry::query()->where('project_id', $projectId);

        $timeEntryCount = (clone $baseQuery)->count();

        $aggregates = (clone $baseQuery)
            ->selectRaw('COALESCE(SUM(hours), 0) as total_hours')
            ->selectRaw('COALESCE(SUM(CASE WHEN regular_hours IS NOT NULL THEN regular_hours ELSE CASE WHEN (COALESCE(hours, 0) - COALESCE(overtime_hours, 0) - COALESCE(double_time_hours, 0)) < 0 THEN 0 ELSE (COALESCE(hours, 0) - COALESCE(overtime_hours, 0) - COALESCE(double_time_hours, 0)) END END), 0) as regular_hours')
            ->selectRaw('COALESCE(SUM(COALESCE(overtime_hours, 0)), 0) as overtime_hours')
            ->selectRaw('COALESCE(SUM(COALESCE(double_time_hours, 0)), 0) as double_time_hours')
            ->first();

        return [
            'time_entry_count' => $timeEntryCount,
            'total_hours' => (float) ($aggregates?->total_hours ?? 0),
            'regular_hours' => (float) ($aggregates?->regular_hours ?? 0),
            'overtime_hours' => (float) ($aggregates?->overtime_hours ?? 0),
            'double_time_hours' => (float) ($aggregates?->double_time_hours ?? 0),
        ];
    }

    /**
     * @return array{
     *     recent_time_entries: EloquentCollection<int, TimecardEntry>,
     *     hours_by_user: EloquentCollection<int, TimecardEntry>,
     * }
     */
    public function detailForProject(string $projectId, int $recentEntriesLimit = 25): array
    {
        $baseQuery = TimecardEntry::query()->where('project_id', $projectId);

        $recentTimeEntries = (clone $baseQuery)
            ->with(['user:id,first_name,last_name', 'costCode:id,code,name'])
            ->latest('date')
            ->limit(max($recentEntriesLimit, 1))
            ->get();

        $hoursByUser = (clone $baseQuery)
            ->selectRaw('user_id, SUM(hours) as total_hours')
            ->with('user:id,first_name,last_name')
            ->groupBy('user_id')
            ->orderByDesc('total_hours')
            ->get();

        return [
            'recent_time_entries' => $recentTimeEntries,
            'hours_by_user' => $hoursByUser,
        ];
    }
}
