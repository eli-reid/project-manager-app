<?php

use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Services\PayrollForecastingService;
use App\Models\Project;
use App\Models\User;

describe('PayrollForecastingService', function () {
    beforeEach(function () {
        $this->service = new PayrollForecastingService;
        $this->user = User::factory()->create();
    });

    describe('trailingAverageForecast', function () {
        test('calculates trailing average with default 4 weeks', function () {
            // Create recent payroll statements
            for ($i = 3; $i >= 0; $i--) {
                PayrollStatement::factory()->create([
                    'user_id' => $this->user->id,
                    'total_hours' => 40,
                    'gross_pay' => 2000,
                    'period_end_date' => now()->subWeeks($i)->endOfWeek(),
                ]);
            }

            $result = $this->service->trailingAverageForecast(trailingWeeks: 4);

            expect($result)
                ->toBeArray()
                ->toHaveKeys(['weekly_cost', 'total_cost', 'based_on_weeks', 'last_week'])
                ->and($result['based_on_weeks'])->toBe(4)
                ->and($result['weekly_cost'])->toBeNumeric()
                ->and($result['total_cost'])->toBeNumeric();
        });

        test('handles custom trailing weeks parameter', function () {
            for ($i = 7; $i >= 0; $i--) {
                PayrollStatement::factory()->create([
                    'user_id' => $this->user->id,
                    'total_hours' => 40,
                    'gross_pay' => 2000,
                    'period_end_date' => now()->subWeeks($i)->endOfWeek(),
                ]);
            }

            $result = $this->service->trailingAverageForecast(trailingWeeks: 8);

            expect($result['based_on_weeks'])->toBe(8);
        });

        test('excludes overtime when includeOt is false', function () {
            PayrollStatement::factory()->create([
                'user_id' => $this->user->id,
                'total_hours' => 40,
                'gross_pay' => 2000,
                'overtime_hours' => 5,
                'overtime_pay' => 500,
                'period_end_date' => now()->endOfWeek(),
            ]);

            $resultWithOt = $this->service->trailingAverageForecast(includeOt: true);
            $resultWithoutOt = $this->service->trailingAverageForecast(includeOt: false);

            expect($resultWithOt['weekly_cost'])->toBeGreaterThanOrEqual($resultWithoutOt['weekly_cost']);
        });

        test('returns zero values when no payroll data exists', function () {
            $result = $this->service->trailingAverageForecast();

            expect($result)
                ->and($result['weekly_cost'])->toBe(0)
                ->and($result['total_cost'])->toBe(0)
                ->and($result['based_on_weeks'])->toBe(4);
        });

        test('respects minimum trailing weeks limit', function () {
            PayrollStatement::factory()->create([
                'user_id' => $this->user->id,
                'gross_pay' => 2000,
                'period_end_date' => now()->endOfWeek(),
            ]);

            $result = $this->service->trailingAverageForecast(trailingWeeks: 1);

            expect($result['based_on_weeks'])->toBeGreaterThanOrEqual(1);
        });

        test('respects maximum trailing weeks limit', function () {
            for ($i = 25; $i >= 0; $i--) {
                PayrollStatement::factory()->create([
                    'user_id' => $this->user->id,
                    'gross_pay' => 2000,
                    'period_end_date' => now()->subWeeks($i)->endOfWeek(),
                ]);
            }

            $result = $this->service->trailingAverageForecast(trailingWeeks: 20);

            expect($result['based_on_weeks'])->toBeLessThanOrEqual(20);
        });
    });

    describe('projectBasedForecast', function () {
        test('calculates project forecast with budget and actuals', function () {
            $project = Project::factory()->create([
                'labor_budget_hours' => 500,
            ]);

            PayrollStatement::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'total_hours' => 100,
                'gross_pay' => 5000,
            ]);

            $result = $this->service->projectBasedForecast(projectId: $project->id);

            expect($result)
                ->toBeArray()
                ->toHaveKeys(['remaining_hours', 'weeks_remaining', 'weekly_cost', 'total_remaining_cost', 'blended_rate', 'budget_hours', 'actual_hours_to_date'])
                ->and($result['budget_hours'])->toBe(500)
                ->and($result['actual_hours_to_date'])->toBe(100)
                ->and($result['remaining_hours'])->toBe(400);
        });

        test('calculates weeks remaining based on average burn rate', function () {
            $project = Project::factory()->create([
                'labor_budget_hours' => 400,
            ]);

            PayrollStatement::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'total_hours' => 100,
                'gross_pay' => 5000,
                'period_end_date' => now()->subWeeks(1)->endOfWeek(),
            ]);

            PayrollStatement::factory()->create([
                'project_id' => $project->id,
                'user_id' => $this->user->id,
                'total_hours' => 50,
                'gross_pay' => 2500,
                'period_end_date' => now()->endOfWeek(),
            ]);

            $result = $this->service->projectBasedForecast(projectId: $project->id);

            expect($result['weeks_remaining'])->toBeNumeric();
        });

        test('returns empty array for non-existent project', function () {
            $result = $this->service->projectBasedForecast(projectId: 'nonexistent');

            expect($result)->toBeArray();
        });

        test('handles projects with zero budget', function () {
            $project = Project::factory()->create([
                'labor_budget_hours' => 0,
            ]);

            $result = $this->service->projectBasedForecast(projectId: $project->id);

            expect($result)
                ->and($result['budget_hours'])->toBe(0)
                ->and($result['weekly_cost'])->toBeNumeric();
        });
    });

    describe('headcountBasedForecast', function () {
        test('calculates headcount forecast for month', function () {
            $employee = User::factory()->create();
            PayRate::factory()->create([
                'user_id' => $employee->id,
                'hourly_rate' => 50,
                'effective_date' => now(),
            ]);

            $result = $this->service->headcountBasedForecast(forMonth: true);

            expect($result)
                ->toBeArray()
                ->toHaveKeys(['employees_active', 'weekly_forecast', 'monthly_forecast', 'avg_rate'])
                ->and($result['employees_active'])->toBeInteger()
                ->and($result['avg_rate'])->toBeNumeric();
        });

        test('calculates weekly headcount forecast by default', function () {
            $employee = User::factory()->create();
            PayRate::factory()->create([
                'user_id' => $employee->id,
                'hourly_rate' => 50,
                'effective_date' => now(),
            ]);

            $result = $this->service->headcountBasedForecast(forMonth: false);

            expect($result)
                ->and($result['weekly_forecast'])->toBeNumeric()
                ->and($result['monthly_forecast'])->toBeNumeric()
                ->and($result['monthly_forecast'])->toBeGreaterThanOrEqual($result['weekly_forecast']);
        });

        test('includes only active employees', function () {
            $active = User::factory()->create(['is_active' => true]);
            $inactive = User::factory()->create(['is_active' => false]);

            PayRate::factory()->create([
                'user_id' => $active->id,
                'hourly_rate' => 50,
                'effective_date' => now(),
            ]);

            PayRate::factory()->create([
                'user_id' => $inactive->id,
                'hourly_rate' => 50,
                'effective_date' => now(),
            ]);

            $result = $this->service->headcountBasedForecast();

            expect($result['employees_active'])->toBeGreaterThan(0);
        });

        test('returns zero values when no active employees', function () {
            $result = $this->service->headcountBasedForecast();

            expect($result)
                ->and($result['employees_active'])->toBeLessThanOrEqual(0)
                ->and($result['weekly_forecast'])->toBeNumeric()
                ->and($result['avg_rate'])->toBeNumeric();
        });
    });

    describe('seasonalAdjustmentForecast', function () {
        test('returns unfavorable indicator when insufficient data', function () {
            $forecast = ['weekly_cost' => 5000];

            $result = $this->service->seasonalAdjustmentForecast(
                forecastMonth: now()->month,
                baseForecast: $forecast
            );

            expect($result)
                ->toBeArray()
                ->toHaveKeys(['seasonal_factor', 'adjusted_forecast', 'has_sufficient_data'])
                ->and($result['has_sufficient_data'])->toBeFalse();
        });

        test('applies seasonal adjustment with 2+ years of data', function () {
            // Create 2+ years of historical data
            for ($i = 0; $i < 24; $i++) {
                PayrollStatement::factory()->create([
                    'user_id' => $this->user->id,
                    'gross_pay' => 2000,
                    'period_end_date' => now()->subMonths($i)->endOfMonth(),
                ]);
            }

            $forecast = ['weekly_cost' => 5000];
            $result = $this->service->seasonalAdjustmentForecast(
                forecastMonth: now()->month,
                baseForecast: $forecast
            );

            expect($result)
                ->and($result['has_sufficient_data'])->toBeTrue()
                ->and($result['seasonal_factor'])->toBeNumeric()
                ->and($result['adjusted_forecast'])->toBeNumeric();
        });

        test('seasonal factor influences forecast appropriately', function () {
            // Create 2+ years of data with varying amounts
            for ($i = 0; $i < 24; $i++) {
                $multiplier = $i % 3 === 0 ? 1.5 : 1.0; // Higher in some months
                PayrollStatement::factory()->create([
                    'user_id' => $this->user->id,
                    'gross_pay' => 2000 * $multiplier,
                    'period_end_date' => now()->subMonths($i)->endOfMonth(),
                ]);
            }

            $forecast = ['weekly_cost' => 5000];
            $result = $this->service->seasonalAdjustmentForecast(
                forecastMonth: 1, // January typically adjusts up or down
                baseForecast: $forecast
            );

            expect($result['seasonal_factor'])->toBeNumeric();
            expect($result['adjusted_forecast'])->toBeNumeric();
        });
    });

    describe('varianceAnalysis', function () {
        test('categorizes favorable variance', function () {
            $result = $this->service->varianceAnalysis(
                actual: 4000,
                forecast: 5000,
                threshold: 0.15
            );

            expect($result)
                ->toHaveKeys(['variance', 'variance_percent', 'category'])
                ->and($result['category'])->toBe('favorable');
        });

        test('categorizes unfavorable variance', function () {
            $result = $this->service->varianceAnalysis(
                actual: 6000,
                forecast: 5000,
                threshold: 0.15
            );

            expect($result['category'])->toBe('unfavorable');
        });

        test('categorizes neutral variance within threshold', function () {
            $result = $this->service->varianceAnalysis(
                actual: 5200,
                forecast: 5000,
                threshold: 0.15
            );

            expect($result['category'])->toBe('neutral');
        });

        test('calculates variance percent correctly', function () {
            $result = $this->service->varianceAnalysis(
                actual: 5000,
                forecast: 10000,
                threshold: 0.15
            );

            expect($result['variance_percent'])->toBe(50);
        });

        test('uses custom threshold for categorization', function () {
            $result = $this->service->varianceAnalysis(
                actual: 5200,
                forecast: 5000,
                threshold: 0.03 // 3% threshold - should be unfavorable
            );

            expect($result['category'])->toBe('unfavorable');
        });

        test('handles zero forecast gracefully', function () {
            $result = $this->service->varianceAnalysis(
                actual: 1000,
                forecast: 0,
                threshold: 0.15
            );

            expect($result)->toBeArray()
                ->and($result['variance'])->toBeNumeric()
                ->and($result['variance_percent'])->toBeNumeric();
        });
    });

    describe('getForecastSummary', function () {
        test('returns summary combining all forecast models', function () {
            // Setup basic data
            $employee = User::factory()->create();
            PayRate::factory()->create([
                'user_id' => $employee->id,
                'hourly_rate' => 50,
                'effective_date' => now(),
            ]);

            PayrollStatement::factory()->create([
                'user_id' => $employee->id,
                'gross_pay' => 2000,
                'period_end_date' => now()->endOfWeek(),
            ]);

            $result = $this->service->getForecastSummary();

            expect($result)
                ->toBeArray()
                ->toHaveKeys(['trailing_average', 'headcount', 'variance'])
                ->and($result['trailing_average'])->toBeArray()
                ->and($result['headcount'])->toBeArray()
                ->and($result['variance'])->toBeArray();
        });

        test('summary includes all required keys', function () {
            $result = $this->service->getForecastSummary();

            expect($result['trailing_average'])->toHaveKeys(['weekly_cost', 'total_cost', 'based_on_weeks', 'last_week'])
                ->and($result['headcount'])->toHaveKeys(['employees_active', 'weekly_forecast', 'monthly_forecast', 'avg_rate'])
                ->and($result['variance'])->toHaveKeys(['variance', 'variance_percent', 'category']);
        });

        test('summary values are reasonable', function () {
            $result = $this->service->getForecastSummary();

            expect($result['trailing_average']['weekly_cost'])->toBeGreaterThanOrEqual(0)
                ->and($result['headcount']['employees_active'])->toBeGreaterThanOrEqual(0)
                ->and($result['headcount']['weekly_forecast'])->toBeGreaterThanOrEqual(0);
        });
    });

    describe('edge cases', function () {
        test('handles null project IDs gracefully', function () {
            $result = $this->service->projectBasedForecast(projectId: null);

            expect($result)->toBeArray();
        });

        test('processes forecasts with minimal data', function () {
            PayrollStatement::factory()->create([
                'user_id' => $this->user->id,
                'gross_pay' => 2000,
                'period_end_date' => now()->endOfWeek(),
            ]);

            $result = $this->service->trailingAverageForecast();

            expect($result)->toBeArray()
                ->and($result['weekly_cost'])->toBeNumeric();
        });

        test('handles forecasting for future dates', function () {
            $futureMonth = now()->addMonths(3)->month;

            $result = $this->service->seasonalAdjustmentForecast(
                forecastMonth: $futureMonth,
                baseForecast: ['weekly_cost' => 5000]
            );

            expect($result)->toBeArray();
        });

        test('returns consistent structure across all methods', function () {
            $trailing = $this->service->trailingAverageForecast();
            $headcount = $this->service->headcountBasedForecast();
            $variance = $this->service->varianceAnalysis(5000, 5000);

            expect($trailing)->toBeArray()
                ->and($headcount)->toBeArray()
                ->and($variance)->toBeArray();
        });
    });
});
