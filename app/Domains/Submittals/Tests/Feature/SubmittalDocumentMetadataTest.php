<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Livewire\Submittals\Form;
use App\Domains\Submittals\Models\Submittal;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('stores default submittal document metadata on pivot sync', function (): void {
    $user = userWithSubmittalMetadataPermissions([
        'submittals.create',
        'projects.view',
        'documents.view',
    ]);

    $reviewer = User::factory()->create();
    $project = Project::factory()->create();

    $document = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(Form::class, ['projectId' => (string) $project->id])
        ->set('type', 'shop_drawing')
        ->set('reviewerIds', [(string) $reviewer->id])
        ->set('items', [[
            'description' => 'Main switchgear submittal package',
            'manufacturer' => null,
            'model' => null,
            'part_number' => null,
            'quantity' => null,
            'unit' => null,
        ]])
        ->set('documentIds', [(string) $document->id])
        ->call('save')
        ->assertHasNoErrors();

    $submittal = Submittal::query()
        ->where('submitted_by_id', (string) $user->id)
        ->latest('created_at')
        ->firstOrFail();

    $pivot = DB::table('submittal_documents')
        ->where('submittal_id', (string) $submittal->id)
        ->where('document_id', (string) $document->id)
        ->first();

    expect($pivot)->toBeObject();
    expect($pivot?->document_role)->toBe(Submittal::DOCUMENT_ROLE_REFERENCE);
    expect($pivot?->document_status)->toBe(Submittal::DOCUMENT_STATUS_ACTIVE);
    expect($pivot?->revision)->toBeNull();
    expect($pivot?->discipline)->toBeNull();
});

it('stores custom submittal document metadata from form input', function (): void {
    $user = userWithSubmittalMetadataPermissions([
        'submittals.create',
        'projects.view',
        'documents.view',
    ]);

    $reviewer = User::factory()->create();
    $project = Project::factory()->create();

    $document = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(Form::class, ['projectId' => (string) $project->id])
        ->set('type', 'material_data')
        ->set('reviewerIds', [(string) $reviewer->id])
        ->set('items', [[
            'description' => 'Panelboard cut sheet package',
            'manufacturer' => null,
            'model' => null,
            'part_number' => null,
            'quantity' => null,
            'unit' => null,
        ]])
        ->set('documentIds', [(string) $document->id])
        ->set('documentMetadata.'.(string) $document->id.'.document_role', Submittal::DOCUMENT_ROLE_PRIMARY)
        ->set('documentMetadata.'.(string) $document->id.'.document_status', Submittal::DOCUMENT_STATUS_DRAFT)
        ->set('documentMetadata.'.(string) $document->id.'.revision', 'Rev B')
        ->set('documentMetadata.'.(string) $document->id.'.discipline', 'Electrical')
        ->call('save')
        ->assertHasNoErrors();

    $submittal = Submittal::query()
        ->where('submitted_by_id', (string) $user->id)
        ->latest('created_at')
        ->firstOrFail();

    $pivot = DB::table('submittal_documents')
        ->where('submittal_id', (string) $submittal->id)
        ->where('document_id', (string) $document->id)
        ->first();

    expect($pivot)->toBeObject();
    expect($pivot?->document_role)->toBe(Submittal::DOCUMENT_ROLE_PRIMARY);
    expect($pivot?->document_status)->toBe(Submittal::DOCUMENT_STATUS_DRAFT);
    expect($pivot?->revision)->toBe('Rev B');
    expect($pivot?->discipline)->toBe('Electrical');
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithSubmittalMetadataPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Submittal Metadata Role '.str()->uuid(),
        'description' => 'Role for submittal metadata tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 25,
    ]);

    $permissionIds = collect($permissions)
        ->map(function (string $permission): ?string {
            [$resource, $action] = explode('.', $permission, 2);

            return Permission::query()
                ->where('resource', $resource)
                ->where('action', $action)
                ->value('id');
        })
        ->filter()
        ->values()
        ->all();

    $role->permissions()->sync($permissionIds);
    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
