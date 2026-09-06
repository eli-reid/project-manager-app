<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Settings\Facades\Settings;
use App\Domains\Documents\Livewire\Admin\Projects\PlansTab;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectTabRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('registers the plans tab in the project tab registry', function (): void {
    $registry = app(ProjectTabRegistry::class);

    expect(array_keys($registry->tabs()))->toContain('plans');
});

it('shows the plans tab to users who can view documents', function (): void {
    $project = Project::factory()->create();
    $user = userWithPlansDomainPermissions(['documents.view', 'projects.view']);

    $registry = app(ProjectTabRegistry::class);

    expect($registry->visibleTabs($project, $user))->toContain('plans');
});

it('renders an empty state when the project has no plans', function (): void {
    $project = Project::factory()->create();
    $user = userWithPlansDomainPermissions(['documents.view', 'projects.view']);

    Livewire::actingAs($user)
        ->test(PlansTab::class, ['project' => $project])
        ->assertSee('No blueprints uploaded for this project yet.');
});

it('uploads a plan into the Plans folder scoped to the project', function (): void {
    Storage::fake('local');
    Settings::set('documents.storage_disk', 'local');

    $user = userWithPlansDomainPermissions([
        'documents.view',
        'documents.manage-project',
        'projects.view',
    ]);

    $project = Project::factory()->create();
    $file = UploadedFile::fake()->create('floor-plan.pdf', 64, 'application/pdf');

    Livewire::actingAs($user)
        ->test(PlansTab::class, ['project' => $project])
        ->set('title', 'Floor Plan Level 1')
        ->set('planSet', 'Architectural')
        ->set('file', $file)
        ->call('save')
        ->assertHasNoErrors();

    $document = Document::query()->where('title', 'Floor Plan Level 1')->first();

    expect($document)->not->toBeNull()
        ->and($document->folder_path)->toBe('Plans/Architectural')
        ->and($document->owner_scope)->toBe(Document::OWNER_SCOPE_PROJECT)
        ->and($document->owner_id)->toBe($project->id);
});

it('only lists documents inside the Plans folder on the plans tab', function (): void {
    $user = userWithPlansDomainPermissions(['documents.view', 'projects.view']);
    $project = Project::factory()->create();

    Document::factory()->create([
        'title' => 'Regular Document',
        'owner_scope' => Document::OWNER_SCOPE_PROJECT,
        'owner_id' => $project->id,
        'folder_path' => 'Submittals',
        'uploaded_by_id' => $user->id,
    ]);

    Document::factory()->create([
        'title' => 'Site Blueprint',
        'owner_scope' => Document::OWNER_SCOPE_PROJECT,
        'owner_id' => $project->id,
        'folder_path' => 'Plans/Structural',
        'uploaded_by_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(PlansTab::class, ['project' => $project])
        ->assertSee('Site Blueprint')
        ->assertDontSee('Regular Document');
});

it('deletes a plan document from the plans tab', function (): void {
    Storage::fake('local');

    $user = userWithPlansDomainPermissions([
        'documents.view',
        'documents.manage-project',
        'documents.delete',
        'projects.view',
    ]);
    $project = Project::factory()->create();

    $document = Document::factory()->create([
        'title' => 'Old Blueprint',
        'owner_scope' => Document::OWNER_SCOPE_PROJECT,
        'owner_id' => $project->id,
        'folder_path' => 'Plans',
        'uploaded_by_id' => $user->id,
        'storage_disk' => 'local',
        'storage_path' => 'documents/project/'.$project->id.'/old-blueprint.pdf',
    ]);
    Storage::disk('local')->put($document->storage_path, 'content');

    Livewire::actingAs($user)
        ->test(PlansTab::class, ['project' => $project])
        ->call('delete', (string) $document->id)
        ->assertHasNoErrors();

    expect(Document::query()->whereKey($document->id)->exists())->toBeFalse();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithPlansDomainPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Plans Test Role '.str()->uuid(),
        'description' => 'Role for plans tab tests',
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
