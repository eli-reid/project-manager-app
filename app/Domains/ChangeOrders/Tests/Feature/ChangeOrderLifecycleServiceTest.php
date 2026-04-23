<?php

use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\ChangeOrders\Services\ChangeOrderLifecycleService;
use Illuminate\Validation\ValidationException;

it('submits a draft change order', function (): void {
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_DRAFT,
        'submitted_at' => null,
    ]);

    $service = app(ChangeOrderLifecycleService::class);
    $submitted = $service->submit($changeOrder);

    expect($submitted->status)->toBe(ChangeOrder::STATUS_SUBMITTED)
        ->and($submitted->submitted_at)->not->toBeNull();
});

it('approves a submitted change order', function (): void {
    $approver = User::factory()->create();
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_SUBMITTED,
        'submitted_at' => now()->subMinute(),
    ]);

    $service = app(ChangeOrderLifecycleService::class);
    $approved = $service->approve($changeOrder, $approver);

    expect($approved->status)->toBe(ChangeOrder::STATUS_APPROVED)
        ->and((string) $approved->approved_by_id)->toBe((string) $approver->id)
        ->and($approved->approved_at)->not->toBeNull();
});

it('rejects a submitted change order with reason', function (): void {
    $rejector = User::factory()->create();
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_SUBMITTED,
    ]);

    $service = app(ChangeOrderLifecycleService::class);
    $rejected = $service->reject($changeOrder, $rejector, 'Needs a pricing correction.');

    expect($rejected->status)->toBe(ChangeOrder::STATUS_REJECTED)
        ->and((string) $rejected->rejected_by_id)->toBe((string) $rejector->id)
        ->and($rejected->rejection_reason)->toBe('Needs a pricing correction.');
});

it('implements an approved change order', function (): void {
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_APPROVED,
    ]);

    $service = app(ChangeOrderLifecycleService::class);
    $implemented = $service->implement($changeOrder);

    expect($implemented->status)->toBe(ChangeOrder::STATUS_IMPLEMENTED)
        ->and($implemented->implemented_at)->not->toBeNull();
});

it('cancels a non-implemented change order', function (): void {
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_SUBMITTED,
    ]);

    $service = app(ChangeOrderLifecycleService::class);
    $cancelled = $service->cancel($changeOrder);

    expect($cancelled->status)->toBe(ChangeOrder::STATUS_CANCELLED)
        ->and($cancelled->cancelled_at)->not->toBeNull();
});

it('blocks invalid approve transitions', function (): void {
    $approver = User::factory()->create();
    $changeOrder = ChangeOrder::factory()->create([
        'status' => ChangeOrder::STATUS_DRAFT,
    ]);

    $service = app(ChangeOrderLifecycleService::class);

    expect(fn () => $service->approve($changeOrder, $approver))
        ->toThrow(ValidationException::class, 'Only submitted change orders may be approved.');
});
