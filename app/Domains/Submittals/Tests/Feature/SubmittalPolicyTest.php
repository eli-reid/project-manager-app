<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Submittals\Models\Submittal;

it('allows owner update and submit when editable and permissions exist', function (): void {
    $user = userWithSubmittalPolicyPermissions([
        'submittals.view',
        'submittals.update',
        'submittals.submit',
    ]);

    $submittal = Submittal::factory()->create([
        'submitted_by_id' => $user->id,
        'status' => Submittal::STATUS_DRAFT,
    ]);

    expect($user->can('update', $submittal))->toBeTrue();
    expect($user->can('submit', $submittal))->toBeTrue();
});

it('denies update and submit when submittal is not editable', function (): void {
    $user = userWithSubmittalPolicyPermissions([
        'submittals.update',
        'submittals.submit',
    ]);

    $submittal = Submittal::factory()->create([
        'submitted_by_id' => $user->id,
        'status' => Submittal::STATUS_APPROVED,
    ]);

    expect($user->can('update', $submittal))->toBeFalse();
    expect($user->can('submit', $submittal))->toBeFalse();
});

it('allows approve/reject only in review statuses', function (): void {
    $reviewer = userWithSubmittalPolicyPermissions([
        'submittals.approve',
        'submittals.reject',
    ]);

    $reviewStatusSubmittal = Submittal::factory()->create([
        'status' => Submittal::STATUS_UNDER_REVIEW,
    ]);

    $approvedSubmittal = Submittal::factory()->create([
        'status' => Submittal::STATUS_APPROVED,
    ]);

    expect($reviewer->can('approve', $reviewStatusSubmittal))->toBeTrue();
    expect($reviewer->can('reject', $reviewStatusSubmittal))->toBeTrue();

    expect($reviewer->can('approve', $approvedSubmittal))->toBeFalse();
    expect($reviewer->can('reject', $approvedSubmittal))->toBeFalse();
});

it('allows distribute only when approved', function (): void {
    $user = userWithSubmittalPolicyPermissions(['submittals.distribute']);

    $approved = Submittal::factory()->create(['status' => Submittal::STATUS_APPROVED]);
    $draft = Submittal::factory()->create(['status' => Submittal::STATUS_DRAFT]);

    expect($user->can('distribute', $approved))->toBeTrue();
    expect($user->can('distribute', $draft))->toBeFalse();
});

/**
 * @param  array<int, string>  $permissions
 */
function userWithSubmittalPolicyPermissions(array $permissions): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create([
        'is_admin' => false,
        'email_verified_at' => now(),
    ]);

    $role = Role::query()->create([
        'name' => 'Submittal Policy Role '.str()->uuid(),
        'description' => 'Role for submittal policy tests',
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
