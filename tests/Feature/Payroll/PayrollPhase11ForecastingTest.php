<?php

use App\Domains\Payroll\Services\PayrollForecastingService;

test('PayrollForecastingService variance analysis categorizes favorable variance', function () {
    $service = new PayrollForecastingService;

    $result = $service->varianceAnalysis(
        actual: 4000,
        forecast: 5000,
        threshold: 0.15
    );

    expect($result)
        ->toBeArray()
        ->toHaveKeys(['variance', 'variance_percent', 'category'])
        ->and($result['category'])->toBe('favorable');
});

test('PayrollForecastingService variance analysis categorizes unfavorable variance', function () {
    $service = new PayrollForecastingService;

    $result = $service->varianceAnalysis(
        actual: 6000,
        forecast: 5000,
        threshold: 0.15
    );

    expect($result['category'])->toBe('unfavorable');
});

test('PayrollForecastingService variance analysis categorizes neutral variance', function () {
    $service = new PayrollForecastingService;

    $result = $service->varianceAnalysis(
        actual: 5200,
        forecast: 5000,
        threshold: 0.15
    );

    expect($result['category'])->toBe('neutral');
});

test('PayrollForecastingService variance analysis calculates percent correctly', function () {
    $service = new PayrollForecastingService;

    $result = $service->varianceAnalysis(
        actual: 5000,
        forecast: 10000,
        threshold: 0.15
    );

    expect($result['variance_percent'])->toEqual(-50.0);
});

test('PayrollForecastingService variance analysis uses custom threshold', function () {
    $service = new PayrollForecastingService;

    $result = $service->varianceAnalysis(
        actual: 5200,
        forecast: 5000,
        threshold: 0.03
    );

    expect($result['category'])->toBe('unfavorable');
});

test('PayrollForecastingService variance analysis handles zero forecast', function () {
    $service = new PayrollForecastingService;

    $result = $service->varianceAnalysis(
        actual: 1000,
        forecast: 0,
        threshold: 0.15
    );

    expect($result)->toBeArray()
        ->and($result['variance'])->toBeNumeric()
        ->and($result['variance_percent'])->toBeNumeric();
});

test('PayrollForecastingService variance returns variance amount', function () {
    $service = new PayrollForecastingService;

    $result = $service->varianceAnalysis(actual: 5000, forecast: 5000);

    expect($result['variance'])->toEqual(0.0);
});

test('PayrollForecastingService variance handles large absolute variances', function () {
    $service = new PayrollForecastingService;

    $result = $service->varianceAnalysis(actual: 100, forecast: 1000, threshold: 0.15);

    expect($result['variance_percent'])->toEqual(-90.0)
        ->and($result['category'])->toBe('favorable');
});

test('PayrollForecastingService can instantiate without parameters', function () {
    $service = new PayrollForecastingService;

    expect($service)->toBeInstanceOf(PayrollForecastingService::class);
});

test('PayrollForecastingService variance analysis method exists', function () {
    $service = new PayrollForecastingService;

    expect(method_exists($service, 'varianceAnalysis'))->toBeTrue();
});

test('PayrollForecastingService trailing average method exists', function () {
    $service = new PayrollForecastingService;

    expect(method_exists($service, 'trailingAverageForecast'))->toBeTrue();
});

test('PayrollForecastingService project forecast method exists', function () {
    $service = new PayrollForecastingService;

    expect(method_exists($service, 'projectBasedForecast'))->toBeTrue();
});

test('PayrollForecastingService headcount method exists', function () {
    $service = new PayrollForecastingService;

    expect(method_exists($service, 'headcountBasedForecast'))->toBeTrue();
});

test('PayrollForecastingService seasonal adjustment method exists', function () {
    $service = new PayrollForecastingService;

    expect(method_exists($service, 'seasonalAdjustmentForecast'))->toBeTrue();
});

test('PayrollForecastingService summary method exists', function () {
    $service = new PayrollForecastingService;

    expect(method_exists($service, 'getForecastSummary'))->toBeTrue();
});
