<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Settings\Facades\Settings;
use App\Core\User\Models\User;
use App\Domains\Documents\Livewire\Admin\Documents\Index as AdminDocumentsIndex;
use App\Domains\Documents\Livewire\Admin\Projects\DocumentsTab;
use App\Domains\Documents\Livewire\User\Documents\Index as UserDocumentsIndex;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests from documents scaffold routes', function (): void {
    get(route('admin.documents.index'))->assertRedirect(route('login'));
    get(route('documents.index'))->assertRedirect(route('login'));
    get(route('documents.mobile.index'))->assertRedirect(route('login'));
    get(route('api.documents.index'))->assertRedirect(route('login'));
});

it('forbids authenticated users without documents permissions', function (): void {
    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    actingAs($user);

    get(route('admin.documents.index'))->assertForbidden();
    get(route('documents.index'))->assertForbidden();
    get(route('documents.mobile.index'))->assertForbidden();
    get(route('api.documents.index'))->assertForbidden();
});

it('allows users with documents view permission to access user-facing documents routes', function (): void {
    $user = userWithDocumentDomainPermissions(['documents.view']);

    actingAs($user);

    get(route('documents.index'))
        ->assertSuccessful()
        ->assertSee('My Documents')
        ->assertSee('Upload a document')
        ->assertSee('Search your library');

    get(route('documents.mobile.index'))
        ->assertSuccessful()
        ->assertSee('Documents Mobile (Scaffold)');

    get(route('api.documents.index'))
        ->assertSuccessful()
        ->assertJson([
            'message' => 'Documents API Scaffold',
        ]);
});

it('allows admins to access the documents admin queue with disk insights', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);

    actingAs($admin);

    get(route('admin.documents.index'))
        ->assertSuccessful()
        ->assertSee('Documents Admin')
        ->assertSee('Storage Used')
        ->assertSee('Disk Space');
});

it('supports project tab full crud for project-owned documents', function (): void {
    Storage::fake('local');
    Settings::set('documents.storage_disk', 'local');

    $user = userWithDocumentDomainPermissions([
        'projects.view',
        'documents.view',
        'documents.create',
        'documents.update',
        'documents.delete',
        'documents.manage-project',
    ]);

    $project = Project::factory()->create();

    actingAs($user);

    Livewire::test(DocumentsTab::class, ['project' => $project])
        ->set('title', 'Project Scope')
        ->set('description', 'Initial file')
        ->set('file', UploadedFile::fake()->create('scope.pdf', 80, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::query()->where('title', 'Project Scope')->first();

    expect($document)->not->toBeNull();
    expect($document?->owner_scope)->toBe(Document::OWNER_SCOPE_PROJECT);
    expect($document?->visibility)->toBe(Document::VISIBILITY_PROJECT);
    expect($document?->ownerProjects()->where('projects.id', $project->id)->exists())->toBeTrue();
    expect($document?->ownerUsers()->exists())->toBeFalse();

    Livewire::test(DocumentsTab::class, ['project' => $project])
        ->call('edit', (string) $document?->id)
        ->set('title', 'Project Scope Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($document?->fresh()->title)->toBe('Project Scope Updated');

    Livewire::test(DocumentsTab::class, ['project' => $project])
        ->call('delete', (string) $document?->id)
        ->assertHasNoErrors();

    expect(Document::query()->whereKey($document?->id)->exists())->toBeFalse();
});

it('supports user promote and demote livewire interactions for user-owned documents', function (): void {
    $user = userWithDocumentDomainPermissions([
        'documents.view',
        'documents.promote-global',
        'documents.demote-private',
    ]);

    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $user->id,
    ]);
    $document->ownerUsers()->sync([$user->id]);

    actingAs($user);

    Livewire::test(UserDocumentsIndex::class)
        ->call('promote', (string) $document->id)
        ->assertHasNoErrors();

    expect($document->fresh()?->visibility)->toBe(Document::VISIBILITY_GLOBAL);

    Livewire::test(UserDocumentsIndex::class)
        ->call('demote', (string) $document->id)
        ->assertHasNoErrors();

    expect($document->fresh()?->visibility)->toBe(Document::VISIBILITY_PRIVATE);
});

it('allows admins to delete any document from the admin queue', function (): void {
    Storage::fake('local');
    Settings::set('documents.storage_disk', 'local');

    $owner = userWithDocumentDomainPermissions(['documents.view']);
    $admin = User::factory()->create([
        'is_admin' => true,
        'email_verified_at' => now(),
    ]);

    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $owner->id,
        'storage_disk' => 'local',
        'storage_path' => 'documents/user/'.$owner->id.'/admin-delete.pdf',
    ]);
    $document->ownerUsers()->sync([$owner->id]);
    Storage::disk('local')->put($document->storage_path, 'document');

    actingAs($admin);

    Livewire::test(AdminDocumentsIndex::class)
        ->call('deleteDocument', (string) $document->id)
        ->assertHasNoErrors();

    expect(Document::query()->whereKey($document->id)->exists())->toBeFalse();
    expect(Storage::disk('local')->exists('documents/user/'.$owner->id.'/admin-delete.pdf'))->toBeFalse();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithDocumentDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Documents Test Role '.str()->uuid(),
        'description' => 'Role for documents domain tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 20,
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
