<?php

namespace App\Domains\Payroll\Reports;

class PayrollForecastingReportDefinitions
{
    public const WEEKLY_LABOR_FORECAST = 'operational.weekly-labor-forecast';

    public const MONTHLY_BUDGET_VS_ACTUAL = 'operational.monthly-budget-vs-actual';

    public const PROJECT_COMPLETION_FORECAST = 'operational.project-completion-forecast';

    public const HEADCOUNT_TREND = 'operational.headcount-trend';

    public const VARIANCE_ANALYSIS = 'operational.variance-analysis';

    public static function all(): array
    {
        return [
            [
                'key' => self::WEEKLY_LABOR_FORECAST,
                'section' => 'operational',
                'title' => 'Weekly Labor Forecast',
                'description' => 'Project future weekly labor costs using trailing average and headcount models.',
                'route' => 'reports.payroll.forecasting.index',
                'badge_label' => 'Forecasting',
                'badge_color' => 'indigo',
                'sort' => 41,
            ],
            [
                'key' => self::MONTHLY_BUDGET_VS_ACTUAL,
                'section' => 'operational',
                'title' => 'Monthly Budget vs Actual',
                'description' => 'Compare monthly labor forecasts against actual payroll spend with variance analysis.',
                'route' => 'reports.payroll.forecasting.index',
                'badge_label' => 'Forecasting',
                'badge_color' => 'indigo',
                'sort' => 42,
            ],
            [
                'key' => self::PROJECT_COMPLETION_FORECAST,
                'section' => 'operational',
                'title' => 'Project Completion Forecast',
                'description' => 'Forecast project completion dates and remaining labor costs based on budget and actuals.',
                'route' => 'reports.payroll.forecasting.index',
                'badge_label' => 'Forecasting',
                'badge_color' => 'indigo',
                'sort' => 43,
            ],
            [
                'key' => self::HEADCOUNT_TREND,
                'section' => 'operational',
                'title' => 'Headcount Trend & Cost Impact',
                'description' => 'Analyze projected labor costs based on current headcount and expected weekly hours.',
                'route' => 'reports.payroll.forecasting.index',
                'badge_label' => 'Forecasting',
                'badge_color' => 'indigo',
                'sort' => 44,
            ],
            [
                'key' => self::VARIANCE_ANALYSIS,
                'section' => 'operational',
                'title' => 'Variance Analysis & Drill-Down',
                'description' => 'Analyze variances between actual and forecasted costs by project, cost code, and employee.',
                'route' => 'reports.payroll.forecasting.index',
                'badge_label' => 'Forecasting',
                'badge_color' => 'indigo',
                'sort' => 45,
            ],
        ];
    }
}
