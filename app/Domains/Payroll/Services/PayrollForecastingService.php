<?php

namespace App\Domains\Payroll\Services;

use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Projects\Models\Project;
use App\Domains\Tasks\Models\Task;
use App\Domains\Timecards\Models\TimecardEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollForecastingService
{
    /**
     * Trailing average forecast: (SUM of actual_labor_cost for trailing N weeks) / N
     *
     * @param  int  $trailingWeeks  Number of weeks to average (default 4, range 2-12)
     * @param  bool  $includeOvertimeInCost  Whether to include OT in cost calculation
     * @return array{weekly_cost: float, total_cost: float, based_on_weeks: int, last_week: string}
     */
    public function trailingAverageForecast(int $trailingWeeks = 4, bool $includeOvertimeInCost = true): array
    {
        $trailingWeeks = max(2, min(12, $trailingWeeks));

        $startDate = now()->subWeeks($trailingWeeks)->startOfWeek()->toDateString();
        $endDate = now()->subDay()->toDateString();

        $statements = PayrollStatement::query()
            ->with('payRun:id,pay_date')
            ->whereHas('payRun', fn ($q) => $q->whereDate('pay_date', '>=', $startDate)->whereDate('pay_date', '<=', $endDate))
            ->get();

        if ($statements->isEmpty()) {
            return [
                'weekly_cost' => 0.0,
                'total_cost' => 0.0,
                'based_on_weeks' => 0,
                'last_week' => now()->subDay()->toDateString(),
            ];
        }

        $totalCost = $statements->sum(function (PayrollStatement $stmt) use ($includeOvertimeInCost): float {
            $baseCost = (float) $stmt->gross_pay;

            if (! $includeOvertimeInCost) {
                $overtimeCost = ((float) $stmt->total_ot_hours + (float) $stmt->total_dt_hours) * ($baseCost / max((float) $stmt->total_regular_hours + (float) $stmt->total_ot_hours + (float) $stmt->total_dt_hours, 1));
                $baseCost -= $overtimeCost;
            }

            return $baseCost;
        });

        $weekCount = $statements->groupBy(fn (PayrollStatement $stmt): string => $stmt->payRun->pay_date->startOfWeek()->toDateString())->count();

        return [
            'weekly_cost' => round($totalCost / max($weekCount, 1), 2),
            'total_cost' => round($totalCost, 2),
            'based_on_weeks' => $weekCount,
            'last_week' => $endDate,
        ];
    }

    /**
     * Project-based forecast: projects remaining labor costs based on budget and current spend
     *
     * @param  string  $projectId  Project ID
     * @return array{remaining_hours: float, weeks_remaining: float, weekly_cost: float, total_remaining_cost: float, blended_rate: float, budget_hours: float, actual_hours_to_date: float}|null
     */
    public function projectBasedForecast(string $projectId): ?array
    {
        $project = Project::query()->find($projectId);
        if ($project === null) {
            return null;
        }

        $budgetHours = $this->resolveProjectBudgetHours($project);
        $actualHours = $this->actualProjectHours($projectId);
        $remainingHours = max(0.0, $budgetHours - $actualHours);

        $avgWeekly = $this->averageWeeklyProjectHours($projectId);
        $weeksRemaining = $avgWeekly > 0 ? round($remainingHours / $avgWeekly, 2) : 0.0;

        $blendedRate = $this->projectBlendedRate($project);
        $weeklyCost = round($avgWeekly * $blendedRate, 2);
        $totalRemainingCost = round($remainingHours * $blendedRate, 2);

        return [
            'remaining_hours' => round($remainingHours, 2),
            'weeks_remaining' => $weeksRemaining,
            'weekly_cost' => $weeklyCost,
            'total_remaining_cost' => $totalRemainingCost,
            'blended_rate' => round($blendedRate, 2),
            'budget_hours' => round($budgetHours, 2),
            'actual_hours_to_date' => round($actualHours, 2),
        ];
    }

    private function resolveProjectBudgetHours(Project $project): float
    {
        $costCodeBudgetHours = (float) $project->costCodes()
            ->where('is_active', true)
            ->sum('budget_hours');

        if ($costCodeBudgetHours > 0) {
            return round($costCodeBudgetHours, 2);
        }

        $taskEstimateHours = (float) Task::query()
            ->where('project_id', $project->id)
            ->sum('estimated_hours');

        return round(max($taskEstimateHours, 0.0), 2);
    }

    private function actualProjectHours(string $projectId): float
    {
        $entries = TimecardEntry::query()
            ->where('project_id', $projectId)
            ->get(['hours', 'regular_hours', 'overtime_hours', 'double_time_hours']);

        return round($this->sumEntryHours($entries), 2);
    }

    private function averageWeeklyProjectHours(string $projectId): float
    {
        $entries = TimecardEntry::query()
            ->where('project_id', $projectId)
            ->whereDate('date', '>=', now()->subWeeks(4)->startOfWeek()->toDateString())
            ->get(['date', 'hours', 'regular_hours', 'overtime_hours', 'double_time_hours']);

        if ($entries->isEmpty()) {
            return 40.0;
        }

        $weeklyHours = $entries
            ->groupBy(fn (TimecardEntry $entry): string => Carbon::parse($entry->date)->startOfWeek()->toDateString())
            ->map(fn (Collection $weekEntries): float => $this->sumEntryHours($weekEntries));

        return round((float) $weeklyHours->avg(), 2);
    }

    private function projectBlendedRate(Project $project): float
    {
        $userIds = TimecardEntry::query()
            ->where('project_id', $project->id)
            ->distinct()
            ->pluck('user_id')
            ->filter()
            ->values();

        if ($userIds->isEmpty()) {
            return 0.0;
        }

        $profiles = PayrollEmployeeProfile::query()
            ->whereIn('user_id', $userIds)
            ->where('status', '!=', 'terminated')
            ->get();

        if ($profiles->isEmpty()) {
            return 0.0;
        }

        $rateResolutionService = app(PayrollRateResolutionService::class);

        $rates = $profiles
            ->map(fn (PayrollEmployeeProfile $profile): ?float => optional(
                $rateResolutionService->resolveForProject($profile, (string) $project->id, now())
            )?->rate_amount)
            ->filter(fn ($rate): bool => $rate !== null)
            ->map(fn ($rate): float => (float) $rate)
            ->values();

        if ($rates->isEmpty()) {
            return 0.0;
        }

        return round((float) $rates->avg(), 2);
    }

    /**
     * @param  Collection<int, TimecardEntry>  $entries
     */
    private function sumEntryHours(Collection $entries): float
    {
        return (float) $entries->sum(function (TimecardEntry $entry): float {
            $detailedHours = (float) ($entry->regular_hours ?? 0)
                + (float) ($entry->overtime_hours ?? 0)
                + (float) ($entry->double_time_hours ?? 0);

            if ($detailedHours > 0) {
                return $detailedHours;
            }

            return (float) ($entry->hours ?? 0);
        });
    }

    /**
     * Headcount-based forecast: projects based on active employees and their rates
     *
     * @param  bool  $forMonth  If true, returns monthly forecast; if false, returns weekly
     * @return array{employees_active: int, weekly_forecast: float, monthly_forecast: float, avg_rate: float}
     */
    public function headcountBasedForecast(bool $forMonth = false): array
    {
        $activeProfiles = PayrollEmployeeProfile::query()
            ->where('status', '!=', 'terminated')
            ->get();

        if ($activeProfiles->isEmpty()) {
            return [
                'employees_active' => 0,
                'weekly_forecast' => 0.0,
                'monthly_forecast' => 0.0,
                'avg_rate' => 0.0,
            ];
        }

        $weeklyForecast = 0.0;
        $totalRate = 0.0;

        foreach ($activeProfiles as $profile) {
            $rate = PayRate::query()
                ->where('payroll_employee_profile_id', $profile->id)
                ->where('effective_date', '<=', now()->toDateString())
                ->whereNull('expiration_date')
                ->latest('effective_date')
                ->first();

            if ($rate === null) {
                continue;
            }

            $expectedWeeklyHours = 40.0; // default, can be overridden per project
            $weeklyForecast += $expectedWeeklyHours * (float) $rate->rate_amount;
            $totalRate += (float) $rate->rate_amount;
        }

        $avgRate = $activeProfiles->count() > 0 ? round($totalRate / $activeProfiles->count(), 2) : 0.0;
        $monthlyForecast = round($weeklyForecast * (52 / 12), 2);

        return [
            'employees_active' => $activeProfiles->count(),
            'weekly_forecast' => round($weeklyForecast, 2),
            'monthly_forecast' => $monthlyForecast,
            'avg_rate' => $avgRate,
        ];
    }

    /**
     * Seasonal adjustment forecast: adjusts base forecast based on seasonal patterns
     * Requires minimum 2 years of historical data
     *
     * @param  string  $forecastMonth  Month to forecast (format: YYYY-MM)
     * @param  float  $baseForecast  Base forecast to adjust
     * @return array{seasonal_factor: float, adjusted_forecast: float, has_sufficient_data: bool}
     */
    public function seasonalAdjustmentForecast(string $forecastMonth, float $baseForecast): array
    {
        $targetMonth = Carbon::parse($forecastMonth.'-01');
        $twoYearsAgo = now()->subYears(2)->toDateString();

        // Get average hours for the target month across past years
        $targetMonthAvgResult = PayrollStatement::query()
            ->whereHas('payRun', fn ($q) => $q->whereDate('pay_date', '>=', $twoYearsAgo))
            ->selectRaw('COALESCE(AVG(total_regular_hours + total_ot_hours + total_dt_hours), 0) as avg_hours')
            ->where('payRun.pay_date', 'like', $targetMonth->format('Y-m').'%')
            ->first();

        $targetMonthAvg = (float) ($targetMonthAvgResult?->avg_hours ?? 0.0);

        // Get overall average hours across all months
        $overallAvgResult = PayrollStatement::query()
            ->whereHas('payRun', fn ($q) => $q->whereDate('pay_date', '>=', $twoYearsAgo))
            ->selectRaw('COALESCE(AVG(total_regular_hours + total_ot_hours + total_dt_hours), 0) as avg_hours')
            ->first();

        $overallAvg = (float) ($overallAvgResult?->avg_hours ?? 1.0);

        $seasonalFactor = $overallAvg > 0 ? round($targetMonthAvg / $overallAvg, 4) : 1.0;
        $adjustedForecast = round($baseForecast * $seasonalFactor, 2);

        // Sufficiency: need at least 2 years of data
        $dataPointCount = PayrollStatement::query()
            ->whereHas('payRun', fn ($q) => $q->whereDate('pay_date', '>=', $twoYearsAgo))
            ->count();

        return [
            'seasonal_factor' => $seasonalFactor,
            'adjusted_forecast' => $adjustedForecast,
            'has_sufficient_data' => $dataPointCount >= 52, // At least 1 year of weekly payroll
        ];
    }

    /**
     * Variance analysis: compares actual vs forecast
     *
     * @param  float  $actual  Actual cost/hours
     * @param  float  $forecast  Forecasted cost/hours
     * @param  float  $threshold  Variance threshold (e.g., 0.15 for ±15%)
     * @return array{variance: float, variance_percent: float, category: 'favorable'|'unfavorable'|'neutral'}
     */
    public function varianceAnalysis(float $actual, float $forecast, float $threshold = 0.15): array
    {
        $variance = round($actual - $forecast, 2);
        $variancePercent = $forecast > 0 ? round((($actual - $forecast) / $forecast) * 100, 2) : 0.0;

        $category = 'neutral';
        if (abs($variancePercent) > ($threshold * 100)) {
            $category = $actual < $forecast ? 'favorable' : 'unfavorable';
        }

        return [
            'variance' => $variance,
            'variance_percent' => $variancePercent,
            'category' => $category,
        ];
    }

    /**
     * Get forecast summary for dashboard: combines all models
     *
     * @return array{trailing_average: array, headcount: array, variance: array}
     */
    public function getForecastSummary(int $trailingWeeks = 4, bool $includeOvertimeInCost = true): array
    {
        $trailingAvg = $this->trailingAverageForecast($trailingWeeks, $includeOvertimeInCost);
        $headcount = $this->headcountBasedForecast(forMonth: false);

        $variance = $this->varianceAnalysis($trailingAvg['weekly_cost'], $headcount['weekly_forecast']);

        return [
            'trailing_average' => $trailingAvg,
            'headcount' => $headcount,
            'variance' => $variance,
        ];
    }
}
