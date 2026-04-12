<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectFinancialsService;

// ─── Service Unit Tests ───────────────────────────────────────────────────────

it('returns zero invoiced total when project has no invoices', function (): void {
    $project = Project::factory()->create(['budget' => null]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['invoiced'])->toBe(0.0)
        ->and($summary['invoice_count'])->toBe(0)
        ->and($summary['budget'])->toBeNull()
        ->and($summary['remaining'])->toBeNull()
        ->and($summary['variance_pct'])->toBeNull();
});

it('sums invoice total_amount correctly', function (): void {
    $project = Project::factory()->create(['budget' => 1000.00]);
    Invoice::factory()->for($project)->create(['total_amount' => 300.00]);
    Invoice::factory()->for($project)->create(['total_amount' => 200.00]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['invoiced'])->toBe(500.0)
        ->and($summary['invoice_count'])->toBe(2);
});

it('calculates remaining budget correctly', function (): void {
    $project = Project::factory()->create(['budget' => 1000.00]);
    Invoice::factory()->for($project)->create(['total_amount' => 400.00]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['remaining'])->toBe(600.0);
});

it('shows negative remaining when over budget', function (): void {
    $project = Project::factory()->create(['budget' => 500.00]);
    Invoice::factory()->for($project)->create(['total_amount' => 750.00]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['remaining'])->toBe(-250.0);
});

it('calculates variance percentage correctly', function (): void {
    $project = Project::factory()->create(['budget' => 1000.00]);
    Invoice::factory()->for($project)->create(['total_amount' => 500.00]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['variance_pct'])->toBe(50.0);
});

it('returns null variance when budget is null', function (): void {
    $project = Project::factory()->create(['budget' => null]);
    Invoice::factory()->for($project)->create(['total_amount' => 100.00]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['variance_pct'])->toBeNull()
        ->and($summary['remaining'])->toBeNull();
});

it('does not include invoices from other projects in totals', function (): void {
    $project = Project::factory()->create(['budget' => 1000.00]);
    $otherProject = Project::factory()->create();
    Invoice::factory()->for($project)->create(['total_amount' => 300.00]);
    Invoice::factory()->for($otherProject)->create(['total_amount' => 9999.00]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['invoiced'])->toBe(300.0)
        ->and($summary['invoice_count'])->toBe(1);
});

// ─── Tab Access Tests ─────────────────────────────────────────────────────────

it('shows financials tab when user has projects.view-financials permission', function (): void {
    $project = Project::factory()->create();
    $user = projectUserWithPermissions(['projects.view', 'projects.view-financials'], $project);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertSuccessful()
        ->assertSee('Financials');
});

it('hides financials tab when user lacks projects.view-financials permission', function (): void {
    $project = Project::factory()->create();
    $user = projectUserWithPermissions(['projects.view'], $project);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertSuccessful()
        ->assertDontSee('Financials');
});

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * @param  array<int, string>  $permissions
 */
if (! function_exists('projectUserWithPermissions')) {
    function projectUserWithPermissions(array $permissions, Project $project): User
    {
        app(DomainPermissionSynchronizer::class)->sync();

        $user = User::factory()->create(['is_admin' => false]);

        $role = Role::query()->create([
            'name' => 'Project Financials Test Role '.str()->uuid(),
            'description' => 'Role created by project financials tests',
            'is_active' => true,
            'built_in' => false,
            'access_level' => 25,
        ]);

        $permissionIds = collect($permissions)
            ->map(function (string $permission): ?string {
                [$resource, $action] = explode('.', $permission, 2);

                $permissionId = Permission::query()
                    ->where('resource', $resource)
                    ->where('action', $action)
                    ->value('id');

                return is_string($permissionId) ? $permissionId : null;
            })
            ->filter()
            ->values()
            ->all();

        $role->permissions()->sync($permissionIds);
        $user->roles()->sync([$role->id]);

        return $user->fresh();
    }
}
