<?php

use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Services\StockReportingService;

it('counts stock orders and returns zero total when no stock cost column exists', function (): void {
    $project = Project::factory()->create();

    StockOrder::factory()->forProject($project)->create([
        'created_at' => '2026-01-10 09:00:00',
    ]);

    StockOrder::factory()->forProject($project)->create([
        'created_at' => '2026-01-20 14:00:00',
    ]);

    StockOrder::factory()->create([
        'created_at' => '2026-02-05 12:00:00',
    ]);

    $metrics = app(StockReportingService::class)->projectMetrics(
        $project->id,
        '2026-01-01',
        '2026-01-31',
    );

    expect($metrics)
        ->toMatchArray([
            'count' => 2,
            'total' => 0.0,
        ]);
});
