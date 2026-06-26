<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\ChangeOrders\Livewire\Admin\ChangeOrders\Form;
use App\Domains\ChangeOrders\Models\ChangeOrder;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('creates change order and stores document metadata from form input', function (): void {
    $user = userWithChangeOrderFormPermissions([
        'change-orders.create',
        'documents.view',
    ]);

    $project = Project::factory()->create();

    $document = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test(Form::class, ['project_id' => (string) $project->id])
        ->set('projectId', (string) $project->id)
        ->set('title', 'Chiller replacement scope revision')
        ->set('description', 'Update labor and materials for revised mechanical routing.')
        ->set('laborAmount', '1200')
        ->set('materialsAmount', '800')
        ->set('documentIds', [(string) $document->id])
        ->set('documentMetadata.'.(string) $document->id.'.document_role', ChangeOrder::DOCUMENT_ROLE_SUPPORTING)
        ->set('documentMetadata.'.(string) $document->id.'.document_status', ChangeOrder::DOCUMENT_STATUS_SUPERSEDED)
        ->set('documentMetadata.'.(string) $document->id.'.revision', 'Rev E')
        ->set('documentMetadata.'.(string) $document->id.'.discipline', 'Mechanical')
        ->call('save')
        ->assertHasNoErrors();

    $changeOrder = ChangeOrder::query()
        ->where('project_id', (string) $project->id)
        ->where('title', 'Chiller replacement scope revision')
        ->latest('created_at')
        ->firstOrFail();

    $pivot = DB::table('change_order_documents')
        ->where('change_order_id', (string) $changeOrder->id)
        ->where('document_id', (string) $document->id)
        ->first();

    expect($pivot)->toBeObject();
    expect($pivot?->document_role)->toBe(ChangeOrder::DOCUMENT_ROLE_SUPPORTING);
    expect($pivot?->document_status)->toBe(ChangeOrder::DOCUMENT_STATUS_SUPERSEDED);
    expect($pivot?->revision)->toBe('Rev E');
    expect($pivot?->discipline)->toBe('Mechanical');
});

it('updates existing change order document metadata from form input', function (): void {
    $user = userWithChangeOrderFormPermissions([
        'change-orders.edit',
        'documents.view',
    ]);

    $project = Project::factory()->create();

    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $project->id,
        'status' => ChangeOrder::STATUS_DRAFT,
    ]);

    $document = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $changeOrder->documents()->sync([
        (string) $document->id => [
            'document_role' => ChangeOrder::DOCUMENT_ROLE_REFERENCE,
            'document_status' => ChangeOrder::DOCUMENT_STATUS_ACTIVE,
            'revision' => null,
            'discipline' => null,
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(Form::class, ['changeOrder' => $changeOrder])
        ->set('documentIds', [(string) $document->id])
        ->set('documentMetadata.'.(string) $document->id.'.document_role', ChangeOrder::DOCUMENT_ROLE_SUPPORTING)
        ->set('documentMetadata.'.(string) $document->id.'.document_status', ChangeOrder::DOCUMENT_STATUS_SUPERSEDED)
        ->set('documentMetadata.'.(string) $document->id.'.revision', 'Rev F')
        ->set('documentMetadata.'.(string) $document->id.'.discipline', 'Electrical')
        ->call('save')
        ->assertHasNoErrors();

    $pivot = DB::table('change_order_documents')
        ->where('change_order_id', (string) $changeOrder->id)
        ->where('document_id', (string) $document->id)
        ->first();

    expect($pivot)->toBeObject();
    expect($pivot?->document_role)->toBe(ChangeOrder::DOCUMENT_ROLE_SUPPORTING);
    expect($pivot?->document_status)->toBe(ChangeOrder::DOCUMENT_STATUS_SUPERSEDED);
    expect($pivot?->revision)->toBe('Rev F');
    expect($pivot?->discipline)->toBe('Electrical');
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithChangeOrderFormPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'CO Form Metadata Role '.str()->uuid(),
        'description' => 'Role for change order form metadata tests',
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
