<?php

declare(strict_types=1);

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Plans\Livewire\Sheets\Viewer;
use App\Domains\Plans\Models\PlanAnnotation;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Models\PlanSheet;
use App\Domains\Plans\Models\PlanSheetRevision;
use App\Domains\Projects\Models\Project;
use Livewire\Livewire;

/**
 * @param  array<int, string>  $permissions
 */
function viewerTestUser(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false, 'email_verified_at' => now()]);

    $role = Role::query()->create([
        'name' => 'Sheet Viewer Test Role '.str()->uuid(),
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()->where('resource', $resource)->where('action', $action)->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}

function makeCurrentRevision(PlanSheet $sheet, array $setAttributes = [], array $revisionAttributes = []): PlanSheetRevision
{
    $set = PlanSet::factory()->create(array_merge(['project_id' => $sheet->project_id], $setAttributes));
    $revision = PlanSheetRevision::factory()->rendered()->create(array_merge([
        'plan_sheet_id' => $sheet->id,
        'plan_set_id' => $set->id,
        'is_current' => true,
    ], $revisionAttributes));
    $sheet->update(['current_revision_id' => $revision->id]);

    return $revision->fresh();
}

// --- Requirement 1: metadata editing gated by role -------------------------------------------------

it('allows a user with plans.update to edit sheet metadata', function (): void {
    $user = viewerTestUser(['plans.view', 'plans.update', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id, 'sheet_number' => 'A-100']);
    makeCurrentRevision($sheet);

    Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('openMetadataEditor')
        ->set('metaSheetNumber', 'A-101')
        ->set('metaTitle', 'Updated Title')
        ->set('metaDiscipline', 'Structural')
        ->call('saveMetadata')
        ->assertHasNoErrors();

    expect($sheet->fresh())
        ->sheet_number->toBe('A-101')
        ->title->toBe('Updated Title')
        ->discipline->toBe('Structural');
});

it('prevents a user without plans.update from editing sheet metadata', function (): void {
    $user = viewerTestUser(['plans.view', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    makeCurrentRevision($sheet);

    Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('openMetadataEditor')
        ->assertForbidden();
});

it('opens a sheet with no metadata set, including the compare link, and allows editing it there', function (): void {
    $user = viewerTestUser(['plans.view', 'plans.update', 'plans.compare', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create([
        'project_id' => $project->id,
        'sheet_number' => null,
        'title' => null,
        'discipline' => null,
    ]);
    makeCurrentRevision($sheet);

    Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->assertOk()
        ->assertSee('Unnumbered')
        ->call('openMetadataEditor')
        ->set('metaSheetNumber', 'A-200')
        ->set('metaTitle', 'First Floor Plan')
        ->call('saveMetadata')
        ->assertHasNoErrors();

    expect($sheet->fresh())
        ->sheet_number->toBe('A-200')
        ->title->toBe('First Floor Plan');
});

it('opens a sheet that has no revisions at all so its metadata can still be edited', function (): void {
    // Reflects a real production state: a sheet split out of a plan set before it has
    // any rendered (or even pending) revision row — previously this 404'd the whole
    // page, blocking the user from ever fixing its metadata.
    $user = viewerTestUser(['plans.view', 'plans.update', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create([
        'project_id' => $project->id,
        'sheet_number' => null,
        'title' => null,
        'discipline' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->assertOk()
        ->assertSee('Unnumbered')
        ->assertSee('No revisions uploaded yet')
        ->call('openMetadataEditor')
        ->set('metaSheetNumber', 'A-300')
        ->set('metaTitle', 'Roof Plan')
        ->call('saveMetadata')
        ->assertHasNoErrors();

    expect($sheet->fresh())
        ->sheet_number->toBe('A-300')
        ->title->toBe('Roof Plan');
});

it('loads only revision fields required by the viewer', function (): void {
    $user = viewerTestUser(['plans.view', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    $revision = makeCurrentRevision($sheet, revisionAttributes: [
        'text_layer' => str_repeat('large OCR payload ', 100),
        'tile_manifest' => ['levels' => [['width' => 4096, 'height' => 4096]]],
    ]);

    $component = Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->assertOk()
        ->assertSee((string) $revision->page_number);

    /** @var PlanSheet $loadedSheet */
    $loadedSheet = $component->get('sheet');
    $loadedRevision = $loadedSheet->revisions->firstOrFail();

    expect($loadedRevision->getAttributes())
        ->toHaveKeys(['id', 'plan_sheet_id', 'plan_set_id', 'revision_label', 'page_number', 'thumbnail_path', 'preview_path', 'is_current', 'created_at'])
        ->not->toHaveKeys(['text_layer', 'tile_manifest', 'detected_sheet_number', 'detected_title']);
});

it('rejects a duplicate sheet number when saving metadata', function (): void {
    $user = viewerTestUser(['plans.view', 'plans.update', 'projects.view']);
    $project = Project::factory()->create();
    PlanSheet::factory()->create(['project_id' => $project->id, 'sheet_number' => 'A-999']);
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id, 'sheet_number' => 'A-100']);
    makeCurrentRevision($sheet);

    Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('openMetadataEditor')
        ->set('metaSheetNumber', 'A-999')
        ->call('saveMetadata')
        ->assertHasErrors(['metaSheetNumber']);
});

// --- Requirement 2: public/private notes with toggle/filter ----------------------------------------

it('allows a user with plans.annotate to create a public note', function (): void {
    $user = viewerTestUser(['plans.view', 'plans.annotate', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    makeCurrentRevision($sheet);

    Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('createAnnotation', 'pin', ['x' => 0.4, 'y' => 0.5], 'Check this detail', PlanAnnotation::VISIBILITY_PUBLIC, false)
        ->assertHasNoErrors();

    expect(PlanAnnotation::query()->where('plan_sheet_id', $sheet->id)->sole())
        ->visibility->toBe('public')
        ->content->toBe('Check this detail');
});

it('prevents a user without plans.annotate from creating notes', function (): void {
    $user = viewerTestUser(['plans.view', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    makeCurrentRevision($sheet);

    Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('createAnnotation', 'pin', ['x' => 0.4, 'y' => 0.5], 'Nope', PlanAnnotation::VISIBILITY_PUBLIC, false)
        ->assertForbidden();
});

it('hides private notes from other users but shows them to the author and managers', function (): void {
    $author = viewerTestUser(['plans.view', 'plans.annotate', 'projects.view']);
    $otherUser = viewerTestUser(['plans.view', 'plans.annotate', 'projects.view']);
    $manager = viewerTestUser(['plans.view', 'plans.annotate', 'plans.manage-annotations', 'projects.view']);

    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    makeCurrentRevision($sheet);

    Livewire::actingAs($author)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('createAnnotation', 'pin', ['x' => 0.2, 'y' => 0.2], 'Only for me', PlanAnnotation::VISIBILITY_PRIVATE, false);

    $othersView = Livewire::actingAs($otherUser)->test(Viewer::class, ['project' => $project, 'sheet' => $sheet]);
    expect($othersView->get('annotationMarkers'))->toBeEmpty();

    $authorsView = Livewire::actingAs($author)->test(Viewer::class, ['project' => $project, 'sheet' => $sheet]);
    expect($authorsView->get('annotationMarkers'))->toHaveCount(1);

    $managersView = Livewire::actingAs($manager)->test(Viewer::class, ['project' => $project, 'sheet' => $sheet]);
    expect($managersView->get('annotationMarkers'))->toHaveCount(1);
});

it('filters notes by visibility and resolved status', function (): void {
    $user = viewerTestUser(['plans.view', 'plans.annotate', 'plans.manage-annotations', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    makeCurrentRevision($sheet);

    PlanAnnotation::factory()->create(['plan_sheet_id' => $sheet->id, 'author_id' => $user->id, 'visibility' => 'public', 'status' => 'open']);
    PlanAnnotation::factory()->create(['plan_sheet_id' => $sheet->id, 'author_id' => $user->id, 'visibility' => 'private', 'status' => 'open']);
    PlanAnnotation::factory()->create(['plan_sheet_id' => $sheet->id, 'author_id' => $user->id, 'visibility' => 'public', 'status' => 'resolved']);

    $component = Livewire::actingAs($user)->test(Viewer::class, ['project' => $project, 'sheet' => $sheet]);

    expect($component->get('annotationMarkers'))->toHaveCount(2); // both open notes, resolved hidden by default

    $component->set('showResolvedNotes', true);
    expect($component->get('annotationMarkers'))->toHaveCount(3);

    $component->set('showPrivateNotes', false);
    expect($component->get('annotationMarkers'))->toHaveCount(2); // both public notes now that resolved is shown
});

// --- Requirement 5 & 6: revisions ordered by plan date, notes browsable by revision ------------------

it('orders revisions by the plan set issue date rather than upload order', function (): void {
    $user = viewerTestUser(['plans.view', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);

    // Uploaded second, but printed first.
    $older = makeCurrentRevision($sheet, ['issued_at' => '2024-01-01'], ['revision_label' => 'Rev A', 'is_current' => false]);
    // Uploaded first, but printed later — should sort ahead of "Rev A".
    $newer = makeCurrentRevision($sheet, ['issued_at' => '2024-06-01'], ['revision_label' => 'Rev B']);

    $component = Livewire::actingAs($user)->test(Viewer::class, ['project' => $project, 'sheet' => $sheet]);

    expect($component->instance()->orderedRevisions->pluck('id')->all())->toBe([$newer->id, $older->id]);
});

it('shows notes pinned to other revisions only when browsing all revisions', function (): void {
    $user = viewerTestUser(['plans.view', 'plans.annotate', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    $current = makeCurrentRevision($sheet, ['issued_at' => '2024-06-01']);
    $older = PlanSheetRevision::factory()->rendered()->create([
        'plan_sheet_id' => $sheet->id,
        'plan_set_id' => PlanSet::factory()->create(['project_id' => $project->id, 'issued_at' => '2024-01-01'])->id,
        'is_current' => false,
    ]);

    PlanAnnotation::factory()->create(['plan_sheet_id' => $sheet->id, 'author_id' => $user->id, 'plan_sheet_revision_id' => $older->id, 'content' => 'Old note']);
    PlanAnnotation::factory()->create(['plan_sheet_id' => $sheet->id, 'author_id' => $user->id, 'plan_sheet_revision_id' => null, 'content' => 'Sheet-following note']);

    $component = Livewire::actingAs($user)->test(Viewer::class, ['project' => $project, 'sheet' => $sheet]);

    expect($component->instance()->currentRevisionAnnotations)->toHaveCount(1)
        ->and($component->instance()->historicalAnnotations)->toHaveCount(0);

    $component->call('setNotesScope', 'all');

    expect($component->instance()->historicalAnnotations)->toHaveCount(1)
        ->and($component->instance()->historicalAnnotations->first()->content)->toBe('Old note');
});

// --- Requirement 7: plan date surfaced in the metadata block ----------------------------------------

it('shows the plan set issue date in the viewer', function (): void {
    $user = viewerTestUser(['plans.view', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    makeCurrentRevision($sheet, ['issued_at' => '2024-03-15']);

    Livewire::actingAs($user)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->assertSee('Plan date')
        ->assertSee('Mar 15, 2024');
});

// --- Annotation lifecycle (update / delete / resolve authorization) --------------------------------

it('lets the author update their own note but blocks other annotators', function (): void {
    $author = viewerTestUser(['plans.view', 'plans.annotate', 'projects.view']);
    $stranger = viewerTestUser(['plans.view', 'plans.annotate', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    makeCurrentRevision($sheet);

    $annotation = PlanAnnotation::factory()->create(['plan_sheet_id' => $sheet->id, 'author_id' => $author->id, 'content' => 'Original']);

    Livewire::actingAs($author)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('updateAnnotation', $annotation->id, 'Edited', 'public')
        ->assertHasNoErrors();

    expect($annotation->fresh()->content)->toBe('Edited');

    Livewire::actingAs($stranger)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('updateAnnotation', $annotation->id, 'Hijacked', 'public')
        ->assertForbidden();
});

it('lets a manager resolve and delete any note', function (): void {
    $author = viewerTestUser(['plans.view', 'plans.annotate', 'projects.view']);
    $manager = viewerTestUser(['plans.view', 'plans.annotate', 'plans.manage-annotations', 'projects.view']);
    $project = Project::factory()->create();
    $sheet = PlanSheet::factory()->create(['project_id' => $project->id]);
    makeCurrentRevision($sheet);

    $annotation = PlanAnnotation::factory()->create(['plan_sheet_id' => $sheet->id, 'author_id' => $author->id, 'status' => 'open']);

    Livewire::actingAs($manager)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('toggleAnnotationStatus', $annotation->id)
        ->assertHasNoErrors();

    expect($annotation->fresh()->status)->toBe('resolved');

    Livewire::actingAs($manager)
        ->test(Viewer::class, ['project' => $project, 'sheet' => $sheet])
        ->call('deleteAnnotation', $annotation->id)
        ->assertHasNoErrors();

    expect(PlanAnnotation::query()->whereKey($annotation->id)->exists())->toBeFalse();
});
