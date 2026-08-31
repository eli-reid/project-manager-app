<?php

use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Services\RFILifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new RFILifecycleService;
    $this->project = Project::factory()->create();
    $this->user = User::factory()->create();
});

it('creates an RFI with sequential numbering', function (): void {
    $rfi1 = $this->service->create($this->project, $this->user, [
        'subject' => 'First RFI',
        'body' => 'Some question',
    ]);

    $rfi2 = $this->service->create($this->project, $this->user, [
        'subject' => 'Second RFI',
    ]);

    expect($rfi1->number)->toBe(1)
        ->and($rfi2->number)->toBe(2)
        ->and($rfi1->status)->toBe(RFI::STATUS_DRAFT)
        ->and($rfi1->project_id)->toBe($this->project->id);
});

it('submits a draft RFI', function (): void {
    $rfi = RFI::factory()->create([
        'project_id' => $this->project->id,
        'status' => RFI::STATUS_DRAFT,
    ]);

    $this->service->submit($rfi);

    expect($rfi->fresh()->status)->toBe(RFI::STATUS_SUBMITTED);
});

it('throws when submitting a non-draft RFI', function (): void {
    $rfi = RFI::factory()->submitted()->create(['project_id' => $this->project->id]);

    expect(fn () => $this->service->submit($rfi))->toThrow(ValidationException::class);
});

it('answers a submitted RFI', function (): void {
    $rfi = RFI::factory()->submitted()->create(['project_id' => $this->project->id]);
    $answerer = User::factory()->create();

    $this->service->answer($rfi, $answerer, [
        'answer' => 'This is the detailed answer.',
        'cost_impact' => '1500.00',
        'schedule_impact_days' => 3,
    ]);

    $fresh = $rfi->fresh();
    expect($fresh->status)->toBe(RFI::STATUS_ANSWERED)
        ->and((string) $fresh->answered_by_id)->toBe((string) $answerer->id)
        ->and($fresh->answer)->toBe('This is the detailed answer.')
        ->and($fresh->answered_at)->not->toBeNull();
});

it('throws when answering a non-submitted RFI', function (): void {
    $rfi = RFI::factory()->create([
        'project_id' => $this->project->id,
        'status' => RFI::STATUS_DRAFT,
    ]);

    expect(fn () => $this->service->answer($rfi, $this->user, ['answer' => 'Answer']))
        ->toThrow(ValidationException::class);
});

it('closes an answered RFI', function (): void {
    $rfi = RFI::factory()->answered()->create(['project_id' => $this->project->id]);

    $this->service->close($rfi);

    expect($rfi->fresh()->status)->toBe(RFI::STATUS_CLOSED);
});

it('throws when closing a non-answered RFI', function (): void {
    $rfi = RFI::factory()->submitted()->create(['project_id' => $this->project->id]);

    expect(fn () => $this->service->close($rfi))->toThrow(ValidationException::class);
});

it('cancels a draft RFI', function (): void {
    $rfi = RFI::factory()->create([
        'project_id' => $this->project->id,
        'status' => RFI::STATUS_DRAFT,
    ]);

    $this->service->cancel($rfi);

    expect($rfi->fresh()->status)->toBe(RFI::STATUS_CANCELLED);
});

it('throws when cancelling a closed RFI', function (): void {
    $rfi = RFI::factory()->closed()->create(['project_id' => $this->project->id]);

    expect(fn () => $this->service->cancel($rfi))->toThrow(ValidationException::class);
});
