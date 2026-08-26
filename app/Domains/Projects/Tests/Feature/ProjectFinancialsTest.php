<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\PaymentReceipts\Models\PaymentReceipt;
use App\Domains\Payroll\Services\PayrollReportingService;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectFinancialsService;
use App\Domains\Tasks\Models\Task;

beforeEach(function (): void {
    $payrollReportingService = Mockery::mock(PayrollReportingService::class);
    $payrollReportingService
        ->shouldReceive('estimatedLaborCostTotalForProject')
        ->andReturn(0.0);

    $this->app->instance(PayrollReportingService::class, $payrollReportingService);
});

// ─── Service Unit Tests ───────────────────────────────────────────────────────

it('returns zero invoiced total when project has no invoices', function (): void {
    $project = Project::factory()->create(['budget' => null]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['invoiced'])->toBe(0.0)
        ->and($summary['invoice_count'])->toBe(0)
        ->and($summary['budget'])->toBeNull()
        ->and($summary['labor_cost'])->toBe(0.0)
        ->and($summary['payments_received'])->toBe(0.0)
        ->and($summary['payment_receipt_count'])->toBe(0)
        ->and($summary['remaining'])->toBeNull()
        ->and($summary['variance_pct'])->toBeNull();
});

it('includes estimated labor cost in the financial summary', function (): void {
    $project = Project::factory()->create(['budget' => 1000.00]);

    $payrollReportingService = Mockery::mock(PayrollReportingService::class);
    $payrollReportingService
        ->shouldReceive('estimatedLaborCostTotalForProject')
        ->once()
        ->with((string) $project->id)
        ->andReturn(325.75);

    $this->app->instance(PayrollReportingService::class, $payrollReportingService);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['labor_cost'])->toBe(325.75);
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

it('includes payment receipts in the financial summary', function (): void {
    $project = Project::factory()->create(['budget' => 1000.00]);
    $otherProject = Project::factory()->create();

    PaymentReceipt::factory()->for($project)->create(['amount' => 450.25]);
    PaymentReceipt::factory()->for($project)->create(['amount' => 99.75]);
    PaymentReceipt::factory()->for($otherProject)->create(['amount' => 9999.00]);

    $summary = app(ProjectFinancialsService::class)->summary($project);

    expect($summary['payments_received'])->toBe(550.0)
        ->and($summary['payment_receipt_count'])->toBe(2);
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

it('shows forecasting tab when user has payroll reports view permission', function (): void {
    $project = Project::factory()->create();
    $user = projectUserWithPermissions(['projects.view', 'payroll-reports.view'], $project);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertSuccessful()
        ->assertSee('Forecasting');
});

it('hides forecasting tab when user lacks payroll reports view permission', function (): void {
    $project = Project::factory()->create();
    $user = projectUserWithPermissions(['projects.view'], $project);

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertSuccessful()
        ->assertDontSee('Forecasting');
});

it('renders the forecasting widget on the project forecasting tab', function (): void {
    $project = Project::factory()->create();
    $user = projectUserWithPermissions(['projects.view', 'payroll-reports.view'], $project);

    $this->actingAs($user)
        ->get(route('projects.show', $project).'?tab=forecasting')
        ->assertSuccessful()
        ->assertSee('Project Payroll Forecast')
        ->assertSee('Open Full Forecasting');
});

it('shows payment receipt totals on the financials tab', function (): void {
    $project = Project::factory()->create();
    $user = projectUserWithPermissions(['projects.view', 'projects.view-financials', 'payment-receipts.view'], $project);

    PaymentReceipt::factory()->for($project)->create([
        'amount' => 1250.00,
        'received_from' => 'Acme Client',
        'reference' => 'ACH-4451',
    ]);

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project).'?tab=financials')
        ->assertSuccessful()
        ->assertSee('Payments Received')
        ->assertSee('1,250.00')
        ->assertSee('Open Pay Recs');
});

it('uses cost code budget hours for the project forecasting widget', function (): void {
    $project = Project::factory()->create();
    $user = projectUserWithPermissions(['projects.view', 'payroll-reports.view'], $project);

    CostCode::factory()->create([
        'project_id' => $project->id,
        'budget_hours' => 120,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('projects.show', $project).'?tab=forecasting')
        ->assertSuccessful()
        ->assertSee('120.00')
        ->assertDontSee('Project-specific forecasting is unavailable');
});

it('falls back to task estimated hours for the project forecasting widget', function (): void {
    $project = Project::factory()->create();
    $user = projectUserWithPermissions(['projects.view', 'payroll-reports.view'], $project);

    Task::factory()->create([
        'project_id' => $project->id,
        'estimated_hours' => 16,
    ]);

    $this->actingAs($user)
        ->get(route('projects.show', $project).'?tab=forecasting')
        ->assertSuccessful()
        ->assertSee('16.00')
        ->assertDontSee('Project-specific forecasting is unavailable');
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
