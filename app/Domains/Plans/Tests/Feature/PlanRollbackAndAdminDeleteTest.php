<?php

declare(strict_types=1);

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Plans\Jobs\PurgePlanDerivativesJob;
use App\Domains\Plans\Jobs\ReindexPlanSetMetadataJob;
use App\Domains\Plans\Livewire\Admin\Projects\PlansTab;
use App\Domains\Plans\Livewire\Sheets\Index;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Plans\Services\PlanSetRollbackService;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function createPlansAdminUser(): User
{
    $role = Role::query()->create([
        'name' => 'Plans Test Admin '.str()->uuid(),
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $p1 = Permission::query()->firstOrCreate(
        ['resource' => 'plans', 'action' => 'view'],
        ['label' => 'View Plans', 'description' => 'View Plans']
    );
    $p2 = Permission::query()->firstOrCreate(
        ['resource' => 'plans', 'action' => 'delete'],
        ['label' => 'Delete Plans', 'description' => 'Delete Plans']
    );
    $p3 = Permission::query()->firstOrCreate(
        ['resource' => 'projects', 'action' => 'view'],
        ['label' => 'View Projects', 'description' => 'View Projects']
    );

    $role->permissions()->sync([$p1->id, $p2->id, $p3->id]);

    $p4 = Permission::query()->firstOrCreate(
        ['resource' => 'plans', 'action' => 'upload'],
        ['label' => 'Upload Plans', 'description' => 'Upload Plans']
    );

    $p5 = Permission::query()->firstOrCreate(
        ['resource' => 'plans', 'action' => 'update'],
        ['label' => 'Update Plans', 'description' => 'Update Plans']
    );

    $role->permissions()->sync([$p1->id, $p2->id, $p3->id, $p4->id, $p5->id]);

    $user = User::factory()->create(['is_admin' => true]);
    $user->roles()->sync([$role->id]);
    User::bumpPermissionCacheVersion();

    return $user;
}

it('rolls back plan set revisions and orphaned sheets on failure', function (): void {
    $project = Project::factory()->create();
    $set = PlanSet::factory()->create(['project_id' => $project->id, 'status' => PlanSet::STATUS_RENDERING]);

    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    $revision = PlanSheetRevision::factory()->create([
        'plan_set_id' => $set->id,
        'plan_sheet_id' => $sheet->id,
        'status' => 'pending',
    ]);

    Storage::disk('local')->put("plans/{$project->id}/{$set->id}/1/preview.png", 'test-preview-content');

    expect(Storage::disk('local')->exists("plans/{$project->id}/{$set->id}/1/preview.png"))->toBeTrue();

    app(PlanSetRollbackService::class)->rollback($set, 'Rendering failed due to memory error');

    expect($set->fresh()->status)->toBe(PlanSet::STATUS_FAILED)
        ->and($set->fresh()->error_message)->toBe('Rendering failed due to memory error')
        ->and($set->fresh()->processed_page_count)->toBe(0)
        ->and(PlanSheetRevision::query()->where('id', $revision->id)->exists())->toBeFalse()
        ->and(PlanSheet::query()->where('id', $sheet->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists("plans/{$project->id}/{$set->id}"))->toBeFalse();
});

it('allows authorized users to delete a plan set from admin', function (): void {
    Queue::fake();

    $user = createPlansAdminUser();

    $project = Project::factory()->create();
    $set = PlanSet::factory()->create(['project_id' => $project->id]);
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    $revision = PlanSheetRevision::factory()->create(['plan_set_id' => $set->id, 'plan_sheet_id' => $sheet->id]);

    Livewire::actingAs($user)
        ->test(PlansTab::class, ['project' => $project])
        ->call('deletePlanSet', $set->id)
        ->assertHasNoErrors();

    expect(PlanSet::query()->where('id', $set->id)->exists())->toBeFalse()
        ->and(PlanSheetRevision::query()->where('id', $revision->id)->exists())->toBeFalse();

    Queue::assertPushed(PurgePlanDerivativesJob::class, fn (PurgePlanDerivativesJob $job): bool => $job->planSetId === $set->id && $job->projectId === $project->id);
});

it('queues metadata reindexing for an existing plan set', function (): void {
    Queue::fake();

    $user = createPlansAdminUser();
    $project = Project::factory()->create();
    $set = PlanSet::factory()->create(['project_id' => $project->id, 'status' => PlanSet::STATUS_READY]);

    Livewire::actingAs($user)
        ->test(PlansTab::class, ['project' => $project])
        ->call('reindexPlanSetMetadata', $set->id)
        ->assertHasNoErrors()
        ->assertSee('Sheet naming has been queued.');

    Queue::assertPushed(ReindexPlanSetMetadataJob::class, fn (ReindexPlanSetMetadataJob $job): bool => $job->planSetId === $set->id);
});

it('allows authorized users to delete an individual sheet from admin', function (): void {
    $user = createPlansAdminUser();

    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    $revision = PlanSheetRevision::factory()->create(['plan_sheet_id' => $sheet->id]);

    Livewire::actingAs($user)
        ->test(Index::class, ['project' => $project])
        ->call('deleteSheet', $sheet->id)
        ->assertHasNoErrors();

    expect(PlanSheet::query()->where('id', $sheet->id)->exists())->toBeFalse()
        ->and(PlanSheetRevision::query()->where('id', $revision->id)->exists())->toBeFalse();
});

it('accepts file upload in PlansTab livewire component', function (): void {
    Storage::fake('local');

    $user = createPlansAdminUser();
    $project = Project::factory()->create();
    $file = UploadedFile::fake()->create('architectural-set.pdf', 100, 'application/pdf');

    Livewire::actingAs($user)
        ->test(PlansTab::class, ['project' => $project])
        ->set('name', 'Architectural Set')
        ->set('discipline', 'Architectural')
        ->set('file', $file)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Plan set uploaded and queued for processing.');

    expect(PlanSet::query()->where('project_id', $project->id)->where('name', 'Architectural Set')->exists())->toBeTrue();
});
