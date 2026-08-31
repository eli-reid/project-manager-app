<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;
use App\Domains\Submittals\Livewire\Admin\Submittals\Index as AdminIndex;
use App\Domains\Submittals\Livewire\Submittals\Index as UserIndex;
use App\Domains\Submittals\Models\Submittal;
use Livewire\Livewire;

it('filters user submittals index by document metadata', function (): void {
    $user = userWithSubmittalIndexFilterPermissions(['submittals.view-any']);

    $project = Project::factory()->create();

    $matchingSubmittal = Submittal::factory()->create([
        'project_id' => $project->id,
        'type' => 'matching-submittal',
        'submitted_by_id' => $user->id,
    ]);

    $nonMatchingSubmittal = Submittal::factory()->create([
        'project_id' => $project->id,
        'type' => 'non-matching-submittal',
        'submitted_by_id' => $user->id,
    ]);

    $matchingDocument = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $nonMatchingDocument = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $matchingSubmittal->documents()->sync([
        (string) $matchingDocument->id => [
            'document_role' => Submittal::DOCUMENT_ROLE_PRIMARY,
            'document_status' => Submittal::DOCUMENT_STATUS_DRAFT,
            'revision' => 'Rev B',
            'discipline' => 'Electrical',
        ],
    ]);

    $nonMatchingSubmittal->documents()->sync([
        (string) $nonMatchingDocument->id => [
            'document_role' => Submittal::DOCUMENT_ROLE_SUPPORTING,
            'document_status' => Submittal::DOCUMENT_STATUS_ACTIVE,
            'revision' => 'Rev A',
            'discipline' => 'Mechanical',
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(UserIndex::class)
        ->set('documentRole', Submittal::DOCUMENT_ROLE_PRIMARY)
        ->set('documentStatus', Submittal::DOCUMENT_STATUS_DRAFT)
        ->set('documentDiscipline', 'Electrical')
        ->set('documentRevision', 'Rev B')
        ->assertSee('matching-submittal')
        ->assertDontSee('non-matching-submittal');
});

it('filters admin submittals queue by document metadata', function (): void {
    $user = userWithSubmittalIndexFilterPermissions(['submittals.view-any']);

    $project = Project::factory()->create();

    $matchingSubmittal = Submittal::factory()->create([
        'project_id' => $project->id,
        'type' => 'admin-matching-submittal',
        'submitted_by_id' => $user->id,
    ]);

    $nonMatchingSubmittal = Submittal::factory()->create([
        'project_id' => $project->id,
        'type' => 'admin-non-matching-submittal',
        'submitted_by_id' => $user->id,
    ]);

    $matchingDocument = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $nonMatchingDocument = Document::factory()->projectOwned()->create([
        'owner_id' => $project->id,
        'uploaded_by_id' => $user->id,
    ]);

    $matchingSubmittal->documents()->sync([
        (string) $matchingDocument->id => [
            'document_role' => Submittal::DOCUMENT_ROLE_COMPLIANCE,
            'document_status' => Submittal::DOCUMENT_STATUS_SUPERSEDED,
            'revision' => 'R-22',
            'discipline' => 'Civil',
        ],
    ]);

    $nonMatchingSubmittal->documents()->sync([
        (string) $nonMatchingDocument->id => [
            'document_role' => Submittal::DOCUMENT_ROLE_REFERENCE,
            'document_status' => Submittal::DOCUMENT_STATUS_ACTIVE,
            'revision' => 'R-20',
            'discipline' => 'Electrical',
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(AdminIndex::class)
        ->set('documentRole', Submittal::DOCUMENT_ROLE_COMPLIANCE)
        ->set('documentStatus', Submittal::DOCUMENT_STATUS_SUPERSEDED)
        ->set('documentDiscipline', 'Civil')
        ->set('documentRevision', 'R-22')
        ->assertSee('admin-matching-submittal')
        ->assertDontSee('admin-non-matching-submittal');
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithSubmittalIndexFilterPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Submittal Index Filters Role '.str()->uuid(),
        'description' => 'Role for submittal index metadata filter tests',
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
