<?php

namespace App\Domains\Timecards\Services;

use App\Core\Settings\Services\WeekSettingsService;
use App\Domains\Payroll\Contracts\PayrollTimecardReadGateway;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentPayrollTimecardReadGateway implements PayrollTimecardReadGateway
{
    public function __construct(private readonly WeekSettingsService $weekSettingsService) {}

    public function approvedEntriesForDateRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $projectId = null,
        array $statuses = [],
        array $with = [],
    ): Collection {
        $resolvedStatuses = $statuses === [] ? [Timecard::STATUS_APPROVED] : $statuses;

        $query = TimecardEntry::query()
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->whereHas('timecard', fn ($timecardQuery) => $timecardQuery->whereIn('status', $resolvedStatuses));

        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', $projectId);
        }

        if ($with !== []) {
            $query->with($with);
        }

        return $query
            ->orderBy('date')
            ->orderBy('user_id')
            ->get();
    }

    public function reviewEntriesForDateRange(
        Carbon $startDate,
        Carbon $endDate,
        ?string $userId = null,
        ?string $projectId = null,
    ): Collection {
        return $this->approvedEntriesForDateRange(
            startDate: $startDate,
            endDate: $endDate,
            projectId: $projectId,
            statuses: [Timecard::STATUS_SUBMITTED, Timecard::STATUS_APPROVED],
            with: ['timecard:id,status', 'user:id,first_name,last_name', 'project:id,name,status', 'costCode:id,code,description'],
        )->when($userId !== null && $userId !== '', fn (Collection $entries) => $entries->where('user_id', $userId)->values());
    }

    public function existingHoursForUserOnDate(string $userId, Carbon $date, ?string $excludeEntryId = null): float
    {
        return (float) TimecardEntry::query()
            ->where('user_id', $userId)
            ->whereDate('date', $date->toDateString())
            ->when($excludeEntryId !== null, fn ($query) => $query->where('id', '!=', $excludeEntryId))
            ->sum('hours');
    }

    public function duplicateEntryExists(
        string $userId,
        Carbon $date,
        ?string $projectId,
        ?string $costCodeId,
        ?string $excludeEntryId = null,
    ): bool {
        return TimecardEntry::query()
            ->where('user_id', $userId)
            ->whereDate('date', $date->toDateString())
            ->where('project_id', $projectId)
            ->where('cost_code_id', $costCodeId)
            ->when($excludeEntryId !== null, fn ($query) => $query->where('id', '!=', $excludeEntryId))
            ->exists();
    }

    public function aggregateHoursByUserAndDate(
        string $userId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $projectId,
        bool $groupByProject,
    ): Collection {
        $query = TimecardEntry::query()
            ->where('user_id', $userId)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString());

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        if ($groupByProject) {
            return $query
                ->selectRaw('DATE(date) as work_date, project_id, SUM(hours) as total_hours')
                ->groupBy('work_date', 'project_id')
                ->get()
                ->mapWithKeys(fn ($row): array => [
                    ((string) $row->work_date).'|'.((string) ($row->project_id ?? '')) => (float) $row->total_hours,
                ]);
        }

        return $query
            ->selectRaw('DATE(date) as work_date, SUM(hours) as total_hours')
            ->groupBy('work_date')
            ->get()
            ->mapWithKeys(fn ($row): array => [
                (string) $row->work_date => (float) $row->total_hours,
            ]);
    }

    public function weeklyEmployeeHoursForWeek(Carbon $weekStart): Collection
    {
        $normalizedWeekStart = $this->weekSettingsService
            ->normalizeWeekStart($weekStart)
            ->startOfDay();
        $weekEnd = $this->weekSettingsService
            ->weekEndFromStart($normalizedWeekStart)
            ->toDateString();

        return TimecardEntry::query()
            ->join('timecards', 'timecards.id', '=', 'timecard_entries.timecard_id')
            ->join('users', 'users.id', '=', 'timecard_entries.user_id')
            ->whereDate('timecard_entries.date', '>=', $normalizedWeekStart->toDateString())
            ->whereDate('timecard_entries.date', '<=', $weekEnd)
            ->whereIn('timecards.status', [Timecard::STATUS_SUBMITTED, Timecard::STATUS_APPROVED])
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->selectRaw('users.id as user_id, users.first_name, users.last_name, ROUND(SUM(timecard_entries.hours), 2) as hours')
            ->get()
            ->map(fn ($row): array => [
                'user_id' => (string) $row->user_id,
                'first_name' => (string) $row->first_name,
                'last_name' => (string) $row->last_name,
                'hours' => (float) $row->hours,
            ]);
    }
}
