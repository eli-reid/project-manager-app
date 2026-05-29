<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\RFIs\Models\RFI;
use App\Domains\RFIs\Policies\RFIPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(DomainPermissionSynchronizer::class)->sync();
    $this->project = Project::factory()->create();
    $this->policy = new RFIPolicy;
});

it('allows viewAny for users with rfis.view-any permission', function (): void {
    $user = rfiPolicyUser(['rfis.view-any']);
    expect($this->policy->viewAny($user))->toBeTrue();
});

it('denies viewAny without permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    expect($this->policy->viewAny($user))->toBeFalse();
});

it('allows view for owner with rfis.view permission', function (): void {
    $user = rfiPolicyUser(['rfis.view']);
    $rfi = RFI::factory()->create([
        'project_id' => $this->project->id,
        'requested_by_id' => $user->id,
    ]);
    expect($this->policy->view($user, $rfi))->toBeTrue();
});

it('denies view for non-owner without view-any permission', function (): void {
    $user = rfiPolicyUser(['rfis.view']);
    $otherUser = User::factory()->create();
    $rfi = RFI::factory()->create([
        'project_id' => $this->project->id,
        'requested_by_id' => $otherUser->id,
    ]);
    expect($this->policy->view($user, $rfi))->toBeFalse();
});

it('allows update on draft RFI for owner with rfis.update permission', function (): void {
    $user = rfiPolicyUser(['rfis.update']);
    $rfi = RFI::factory()->create([
        'project_id' => $this->project->id,
        'requested_by_id' => $user->id,
        'status' => RFI::STATUS_DRAFT,
    ]);
    expect($this->policy->update($user, $rfi))->toBeTrue();
});

it('denies update on submitted RFI', function (): void {
    $user = rfiPolicyUser(['rfis.update', 'rfis.view-any']);
    $rfi = RFI::factory()->submitted()->create(['project_id' => $this->project->id]);
    expect($this->policy->update($user, $rfi))->toBeFalse();
});

it('allows answer on submitted RFI with rfis.answer permission', function (): void {
    $user = rfiPolicyUser(['rfis.answer']);
    $rfi = RFI::factory()->submitted()->create(['project_id' => $this->project->id]);
    expect($this->policy->answer($user, $rfi))->toBeTrue();
});

it('denies answer on non-submitted RFI', function (): void {
    $user = rfiPolicyUser(['rfis.answer']);
    $rfi = RFI::factory()->create([
        'project_id' => $this->project->id,
        'status' => RFI::STATUS_DRAFT,
    ]);
    expect($this->policy->answer($user, $rfi))->toBeFalse();
});

it('allows close on answered RFI with rfis.close permission', function (): void {
    $user = rfiPolicyUser(['rfis.close']);
    $rfi = RFI::factory()->answered()->create(['project_id' => $this->project->id]);
    expect($this->policy->close($user, $rfi))->toBeTrue();
});

it('denies close on non-answered RFI', function (): void {
    $user = rfiPolicyUser(['rfis.close']);
    $rfi = RFI::factory()->submitted()->create(['project_id' => $this->project->id]);
    expect($this->policy->close($user, $rfi))->toBeFalse();
});

it('denies cancel on closed RFI', function (): void {
    $user = rfiPolicyUser(['rfis.update']);
    $rfi = RFI::factory()->closed()->create(['project_id' => $this->project->id]);
    expect($this->policy->cancel($user, $rfi))->toBeFalse();
});

it('allows cancel on submitted RFI with rfis.update permission', function (): void {
    $user = rfiPolicyUser(['rfis.update']);
    $rfi = RFI::factory()->submitted()->create(['project_id' => $this->project->id]);
    expect($this->policy->cancel($user, $rfi))->toBeTrue();
});

/**
 * @param  array<int, string>  $permissions
 */
function rfiPolicyUser(array $permissions): User
{
    $user = User::factory()->create(['is_admin' => false]);

    $role = Role::query()->create([
        'name' => 'RFI Policy Test Role '.str()->uuid(),
        'description' => 'Role for RFI policy tests',
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
