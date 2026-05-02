<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
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

it('shows project upload constraints in project documents tab ui', function (): void {
    Settings::set('documents.max_file_size', '2048');
    Settings::set('documents.allowed_types', 'pdf,png,jpg');

    $user = userWithDocumentDomainPermissions([
        'projects.view',
        'documents.view',
        'documents.create',
        'documents.manage-project',
    ]);

    $project = Project::factory()->create();

    actingAs($user);

    Livewire::test(DocumentsTab::class, ['project' => $project])
        ->assertSee('Max 2.0 MB')
        ->assertSee('Allowed: PDF, PNG, JPG');
});

it('renders bordered form controls in the project documents tab', function (): void {
    $user = userWithDocumentDomainPermissions([
        'projects.view',
        'documents.view',
        'documents.create',
        'documents.manage-project',
    ]);

    $project = Project::factory()->create();

    actingAs($user);

    Livewire::test(DocumentsTab::class, ['project' => $project])
        ->assertSeeHtml('wire:model.live="search" placeholder="Search documents..." class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"')
        ->assertSeeHtml('x-model="titleValue" class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"')
        ->assertSeeHtml('wire:model="description" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"');
});

it('validates project upload size using documents max file size setting', function (): void {
    Storage::fake('local');
    Settings::set('documents.storage_disk', 'local');
    Settings::set('documents.max_file_size', '100');
    Settings::set('documents.allowed_types', 'pdf');

    $user = userWithDocumentDomainPermissions([
        'projects.view',
        'documents.view',
        'documents.create',
        'documents.manage-project',
    ]);

    $project = Project::factory()->create();

    actingAs($user);

    Livewire::test(DocumentsTab::class, ['project' => $project])
        ->set('title', 'Oversized Upload')
        ->set('file', UploadedFile::fake()->create('large.pdf', 101, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['file' => ['max']]);

    expect(Document::query()->where('title', 'Oversized Upload')->exists())->toBeFalse();
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

it('renders the documents page with the share panel open for an owned document', function (): void {
    $user = userWithDocumentDomainPermissions([
        'documents.view',
        'documents.share',
    ]);

    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $user->id,
    ]);
    $document->ownerUsers()->sync([$user->id]);

    actingAs($user);

    get(route('documents.index', ['share' => $document->id]))
        ->assertSuccessful()
        ->assertSee('Share '.$document->title)
        ->assertSee('Create Share Link');
});

it('allows owner to download their user-owned document', function (): void {
    Storage::fake('local');

    $user = userWithDocumentDomainPermissions(['documents.view']);

    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $user->id,
        'storage_disk' => 'local',
        'storage_path' => 'documents/user/'.$user->id.'/download-test.pdf',
        'original_name' => 'download-test.pdf',
    ]);
    $document->ownerUsers()->sync([$user->id]);
    Storage::disk('local')->put($document->storage_path, 'document-content');

    actingAs($user);

    get(route('documents.download', $document))
        ->assertSuccessful();
});

it('forbids non-owners from downloading private user-owned documents', function (): void {
    Storage::fake('local');

    $owner = userWithDocumentDomainPermissions(['documents.view']);
    $otherUser = userWithDocumentDomainPermissions(['documents.view']);

    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $owner->id,
        'storage_disk' => 'local',
        'storage_path' => 'documents/user/'.$owner->id.'/private-download-test.pdf',
        'original_name' => 'private-download-test.pdf',
    ]);
    $document->ownerUsers()->sync([$owner->id]);
    Storage::disk('local')->put($document->storage_path, 'document-content');

    actingAs($otherUser);

    get(route('documents.download', $document))
        ->assertForbidden();
});

it('allows authorized user to download a project-owned document', function (): void {
    Storage::fake('local');

    $user = userWithDocumentDomainPermissions(['documents.view', 'projects.view']);

    $project = Project::factory()->create();

    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_PROJECT,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $user->id,
        'storage_disk' => 'local',
        'storage_path' => 'documents/project/'.$project->id.'/project-doc.pdf',
        'original_name' => 'project-doc.pdf',
    ]);
    $document->ownerProjects()->sync([$project->id]);
    Storage::disk('local')->put($document->storage_path, 'project-document-content');

    actingAs($user);

    get(route('documents.download', $document))
        ->assertSuccessful();
});

it('forbids users without project access from downloading project-owned documents', function (): void {
    Storage::fake('local');

    $uploader = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
    $outsider = userWithDocumentDomainPermissions(['documents.view']);

    $project = Project::factory()->create();

    $document = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_PROJECT,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $uploader->id,
        'storage_disk' => 'local',
        'storage_path' => 'documents/project/'.$project->id.'/restricted-doc.pdf',
        'original_name' => 'restricted-doc.pdf',
    ]);
    $document->ownerProjects()->sync([$project->id]);
    Storage::disk('local')->put($document->storage_path, 'content');

    actingAs($outsider);

    get(route('documents.download', $document))
        ->assertForbidden();
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
