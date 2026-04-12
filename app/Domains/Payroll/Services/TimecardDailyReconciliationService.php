<?php

namespace App\Domains\Payroll\Services;

use App\Core\Settings\Facades\Settings;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Payroll\Data\ReconciliationRow;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimecardDailyReconciliationService
{
    /**
     * Reconcile timecard entries against daily reports for a single employee over a
     * date range. Returns one ReconciliationRow per unique date (or date+project when
     * `payroll.reconciliation.require_project_match` is enabled).
     *
     * Returns an empty collection when reconciliation is disabled via settings.
     *
     * @return Collection<int, ReconciliationRow>
     */
    public function reconcile(
        string $userId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $projectId = null,
    ): Collection {
        if (! $this->isEnabled()) {
            return collect();
        }

        $tolerance = (float) Settings::get('payroll.reconciliation.hours_tolerance', 0.25)->toString('0.25');
        $requireProject = Settings::get('payroll.reconciliation.require_project_match', true)->toBool(true);

        $timecardMap = $this->aggregateTimecardHours($userId, $startDate, $endDate, $projectId, $requireProject);
        $dailyMap = $this->aggregateDailyHours($userId, $startDate, $endDate, $projectId, $requireProject);

        $allKeys = $timecardMap->keys()
            ->merge($dailyMap->keys())
            ->unique()
            ->sort()
            ->values();

        return $allKeys->map(function (string $key) use ($userId, $timecardMap, $dailyMap, $tolerance, $requireProject): ReconciliationRow {
            [$date, $projId] = $this->parseKey($key, $requireProject);

            $tcHours = (float) $timecardMap->get($key, 0.0);
            $drHours = (float) $dailyMap->get($key, 0.0);
            $variance = abs($tcHours - $drHours);

            return new ReconciliationRow(
                userId: $userId,
                projectId: $projId,
                date: $date,
                timecardHours: $tcHours,
                dailyHours: $drHours,
                variance: $variance,
                isMismatch: $variance > $tolerance,
            );
        });
    }

    // ------------------------------------------------------------------
    // Settings helpers
    // ------------------------------------------------------------------

    protected function isEnabled(): bool
    {
        return Settings::get('payroll.reconciliation.enabled', true)->toBool(true);
    }

    // ------------------------------------------------------------------
    // Aggregation helpers
    // ------------------------------------------------------------------

    /**
     * @return Collection<string, float>
     */
    protected function aggregateTimecardHours(
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
            $rows = $query
                ->selectRaw('DATE(date) as work_date, project_id, SUM(hours) as total_hours')
                ->groupBy('work_date', 'project_id')
                ->get();

            return $rows->mapWithKeys(fn ($row): array => [
                $this->makeKey((string) $row->work_date, (string) ($row->project_id ?? '')) => (float) $row->total_hours,
            ]);
        }

        $rows = $query
            ->selectRaw('DATE(date) as work_date, SUM(hours) as total_hours')
            ->groupBy('work_date')
            ->get();

        return $rows->mapWithKeys(fn ($row): array => [
            $this->makeKey((string) $row->work_date, '') => (float) $row->total_hours,
        ]);
    }

    /**
     * @return Collection<string, float>
     */
    protected function aggregateDailyHours(
        string $userId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $projectId,
        bool $groupByProject,
    ): Collection {
        $query = DailyReport::query()
            ->where('user_id', $userId)
            ->whereDate('report_date', '>=', $startDate->toDateString())
            ->whereDate('report_date', '<=', $endDate->toDateString());

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        if ($groupByProject) {
            $rows = $query
                ->selectRaw('DATE(report_date) as work_date, project_id, SUM(total_hours) as total_hours')
                ->groupBy('work_date', 'project_id')
                ->get();

            return $rows->mapWithKeys(fn ($row): array => [
                $this->makeKey((string) $row->work_date, (string) ($row->project_id ?? '')) => (float) $row->total_hours,
            ]);
        }

        $rows = $query
            ->selectRaw('DATE(report_date) as work_date, SUM(total_hours) as total_hours')
            ->groupBy('work_date')
            ->get();

        return $rows->mapWithKeys(fn ($row): array => [
            $this->makeKey((string) $row->work_date, '') => (float) $row->total_hours,
        ]);
    }

    // ------------------------------------------------------------------
    // Key encoding
    // ------------------------------------------------------------------

    protected function makeKey(string $date, string $projectId): string
    {
        return $projectId !== '' ? "{$date}|{$projectId}" : $date;
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    protected function parseKey(string $key, bool $requireProject): array
    {
        if ($requireProject && str_contains($key, '|')) {
            [$date, $projId] = explode('|', $key, 2);

            return [$date, $projId !== '' ? $projId : null];
        }

        return [$key, null];
    }
}
