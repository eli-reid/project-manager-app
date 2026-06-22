<?php

use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Projects\Models\Project;

it('exposes the supported change order statuses', function (): void {
    expect(ChangeOrder::statuses())
        ->toContain(
            ChangeOrder::STATUS_DRAFT,
            ChangeOrder::STATUS_SUBMITTED,
            ChangeOrder::STATUS_APPROVED,
            ChangeOrder::STATUS_REJECTED,
            ChangeOrder::STATUS_IMPLEMENTED,
            ChangeOrder::STATUS_CANCELLED,
        );
});

it('recalculates total amount from labor and materials', function (): void {
    $changeOrder = ChangeOrder::factory()->make([
        'labor_amount' => 1234.25,
        'materials_amount' => 765.50,
    ]);

    $changeOrder->recalculateTotal();

    expect((float) $changeOrder->total_amount)->toBe(1999.75);
});

it('belongs to a project', function (): void {
    $project = Project::factory()->create();
    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $project->id,
    ]);

    expect((string) $changeOrder->project?->id)->toBe((string) $project->id);
});
