<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Projects\Models\Project;

it('applies document view policy matrix for user-owned visibility', function (): void {
    $owner = userWithDocumentPolicyPermissions([
        'documents.view',
        'documents.update',
        'documents.delete',
        'documents.promote-global',
        'documents.demote-private',
    ]);

    $viewer = userWithDocumentPolicyPermissions(['documents.view']);
    $stranger = userWithDocumentPolicyPermissions(['documents.view']);

    $privateDocument = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $owner->id,
    ]);
    $privateDocument->ownerUsers()->sync([$owner->id]);

    $globalDocument = Document::factory()->global()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'uploaded_by_id' => $owner->id,
    ]);
    $globalDocument->ownerUsers()->sync([$owner->id]);

    expect($owner->can('view', $privateDocument))->toBeTrue();
    expect($owner->can('update', $privateDocument))->toBeTrue();
    expect($owner->can('delete', $privateDocument))->toBeTrue();
    expect($owner->can('promoteToGlobal', $privateDocument))->toBeTrue();

    expect($viewer->can('view', $privateDocument))->toBeFalse();
    expect($viewer->can('view', $globalDocument))->toBeTrue();
    expect($stranger->can('update', $privateDocument))->toBeFalse();
    expect($stranger->can('delete', $privateDocument))->toBeFalse();
});

it('requires manage-project and project access for project-owned document changes', function (): void {
    $project = Project::factory()->create();

    $manager = userWithDocumentPolicyPermissions([
        'projects.view',
        'documents.view',
        'documents.update',
        'documents.delete',
        'documents.manage-project',
    ]);

    $limited = userWithDocumentPolicyPermissions([
        'projects.view',
        'documents.view',
        'documents.update',
        'documents.delete',
    ]);

    $projectDocument = Document::factory()->projectOwned()->create([
        'uploaded_by_id' => $manager->id,
    ]);
    $projectDocument->ownerProjects()->sync([$project->id]);

    expect($manager->can('view', $projectDocument))->toBeTrue();
    expect($manager->can('update', $projectDocument))->toBeTrue();
    expect($manager->can('delete', $projectDocument))->toBeTrue();

    expect($limited->can('view', $projectDocument))->toBeTrue();
    expect($limited->can('update', $projectDocument))->toBeFalse();
    expect($limited->can('delete', $projectDocument))->toBeFalse();
});

it('denies attaching user-owned documents to projects', function (): void {
    $project = Project::factory()->create();

    $user = userWithDocumentPolicyPermissions([
        'projects.view',
        'documents.view',
        'documents.update',
        'documents.delete',
        'documents.manage-project',
    ]);

    $userOwnedDocument = Document::factory()->create([
        'owner_scope' => Document::OWNER_SCOPE_USER,
        'visibility' => Document::VISIBILITY_PRIVATE,
        'uploaded_by_id' => $user->id,
    ]);
    $userOwnedDocument->ownerUsers()->sync([$user->id]);

    expect($user->can('attachToProject', [$userOwnedDocument, $project]))->toBeFalse();
    expect($user->can('detachFromProject', [$userOwnedDocument, $project]))->toBeFalse();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithDocumentPolicyPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Document Policy Role '.str()->uuid(),
        'description' => 'Role for document policy matrix tests',
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
