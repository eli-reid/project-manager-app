<?php

use App\Core\Identity\Models\User;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Models\SubmittalApproval;
use App\Domains\Submittals\Services\SubmittalLifecycleService;
use Illuminate\Validation\ValidationException;

it('submits a draft submittal', function (): void {
    $submittal = Submittal::factory()->create([
        'status' => Submittal::STATUS_DRAFT,
        'submitted_at' => null,
    ]);

    $service = app(SubmittalLifecycleService::class);
    $submitted = $service->submit($submittal);

    expect($submitted->statusValue())->toBe(Submittal::STATUS_UNDER_REVIEW)
        ->and($submitted->submitted_at)->not->toBeNull();
});

it('assigns review chain and advances to approval', function (): void {
    $reviewerOne = User::factory()->create();
    $reviewerTwo = User::factory()->create();

    $submittal = Submittal::factory()->create([
        'status' => Submittal::STATUS_DRAFT,
    ]);

    $service = app(SubmittalLifecycleService::class);
    $service->assignReviewers($submittal, [(string) $reviewerOne->id, (string) $reviewerTwo->id]);

    $submittal->refresh();
    expect((string) $submittal->current_reviewer_id)->toBe((string) $reviewerOne->id)
        ->and($submittal->approvals()->count())->toBe(2);

    $service->approve($submittal, $reviewerOne, 'Looks good from discipline one.');
    $submittal->refresh();

    expect((string) $submittal->current_reviewer_id)->toBe((string) $reviewerTwo->id)
        ->and($submittal->statusValue())->toBe(Submittal::STATUS_ARCHITECT_REVIEW);

    $service->approve($submittal, $reviewerTwo, 'Approved final.');
    $submittal->refresh();

    expect($submittal->statusValue())->toBe(Submittal::STATUS_APPROVED)
        ->and($submittal->approved_at)->not->toBeNull();
});

it('rejects at a review step and captures reason', function (): void {
    $reviewer = User::factory()->create();

    $submittal = Submittal::factory()->create([
        'status' => Submittal::STATUS_UNDER_REVIEW,
        'submitted_at' => now()->subMinute(),
        'current_reviewer_id' => $reviewer->id,
    ]);

    SubmittalApproval::factory()->create([
        'submittal_id' => $submittal->id,
        'step' => 1,
        'reviewer_id' => $reviewer->id,
        'status' => SubmittalApproval::STATUS_PENDING,
    ]);

    $service = app(SubmittalLifecycleService::class);
    $rejected = $service->reject($submittal, $reviewer, 'Fixture wattage does not match spec.');

    expect($rejected->statusValue())->toBe(Submittal::STATUS_REJECTED)
        ->and($rejected->rejection_reason)->toBe('Fixture wattage does not match spec.')
        ->and($rejected->rejected_at)->not->toBeNull();
});

it('prevents distribution when not approved', function (): void {
    $submittal = Submittal::factory()->create([
        'status' => Submittal::STATUS_UNDER_REVIEW,
    ]);

    $service = app(SubmittalLifecycleService::class);

    expect(fn () => $service->distribute($submittal))
        ->toThrow(ValidationException::class, 'Only approved submittals may be distributed.');
});
