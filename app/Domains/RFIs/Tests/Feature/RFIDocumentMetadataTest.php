<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Livewire\Admin\RFIs\Index;
use App\Domains\RFIs\Models\RFI;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('stores default document metadata when creating an RFI from project tab', function (): void {
    $user = userWithRfiDocumentMetadataPermissions([
        'rfis.create',
        'documents.view',
    ]);

    $project = Project::factory()->create();

    $document = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class, [
        'project' => $project,
        'embedded' => true,
        'isCreateMode' => true,
    ])
        ->set('subject', 'RFI for beam embed alignment')
        ->set('body', 'Need clarification on embed layout at grid B4.')
        ->set('documentIds', [(string) $document->id])
        ->call('submitRfi')
        ->assertHasNoErrors();

    $rfi = RFI::query()
        ->where('project_id', (string) $project->id)
        ->where('subject', 'RFI for beam embed alignment')
        ->latest('created_at')
        ->firstOrFail();

    $pivot = DB::table('rfi_documents')
        ->where('rfi_id', (string) $rfi->id)
        ->where('document_id', (string) $document->id)
        ->first();

    expect($rfi->status)->toBe(RFI::STATUS_SUBMITTED);
    expect($pivot)->toBeObject();
    expect($pivot?->document_role)->toBe(RFI::DOCUMENT_ROLE_REFERENCE);
    expect($pivot?->document_status)->toBe(RFI::DOCUMENT_STATUS_ACTIVE);
    expect($pivot?->revision)->toBeNull();
    expect($pivot?->discipline)->toBeNull();
});

it('stores custom document metadata when creating an RFI from project tab', function (): void {
    $user = userWithRfiDocumentMetadataPermissions([
        'rfis.create',
        'documents.view',
    ]);

    $project = Project::factory()->create();

    $document = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class, [
        'project' => $project,
        'embedded' => true,
        'isCreateMode' => true,
    ])
        ->set('subject', 'RFI for slab opening coordinates')
        ->set('body', 'Confirm final opening dimensions for MEP penetrations.')
        ->set('documentIds', [(string) $document->id])
        ->set('documentMetadata.'.(string) $document->id.'.document_role', RFI::DOCUMENT_ROLE_RESPONSE)
        ->set('documentMetadata.'.(string) $document->id.'.document_status', RFI::DOCUMENT_STATUS_SUPERSEDED)
        ->set('documentMetadata.'.(string) $document->id.'.revision', 'Rev C')
        ->set('documentMetadata.'.(string) $document->id.'.discipline', 'MEP')
        ->call('submitRfi')
        ->assertHasNoErrors();

    $rfi = RFI::query()
        ->where('project_id', (string) $project->id)
        ->where('subject', 'RFI for slab opening coordinates')
        ->latest('created_at')
        ->firstOrFail();

    $pivot = DB::table('rfi_documents')
        ->where('rfi_id', (string) $rfi->id)
        ->where('document_id', (string) $document->id)
        ->first();

    expect($rfi->status)->toBe(RFI::STATUS_SUBMITTED);
    expect($pivot)->toBeObject();
    expect($pivot?->document_role)->toBe(RFI::DOCUMENT_ROLE_RESPONSE);
    expect($pivot?->document_status)->toBe(RFI::DOCUMENT_STATUS_SUPERSEDED);
    expect($pivot?->revision)->toBe('Rev C');
    expect($pivot?->discipline)->toBe('MEP');
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithRfiDocumentMetadataPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'RFI Document Metadata Role '.str()->uuid(),
        'description' => 'Role for RFI document metadata tests',
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
