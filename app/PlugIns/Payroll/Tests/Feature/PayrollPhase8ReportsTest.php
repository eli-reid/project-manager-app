<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Domains\Payroll\Livewire\User\Reports\CertifiedPayroll\Index as CertifiedPayrollIndex;
use App\Domains\Payroll\Livewire\User\Reports\LaborCost\Index as LaborCostIndex;
use App\Domains\Payroll\Livewire\User\Reports\UnionRemittance\Index as UnionRemittanceIndex;
use App\Domains\Payroll\Models\Deduction;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Projects\Models\CostCode;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('renders certified payroll report rows from approved timecards', function (): void {
    $user = payrollReportsUserWithPermissions(['payroll-reports.view']);

    $project = Project::factory()->create([
        'project_number' => 'PRJ-8001',
    ]);

    $costCode = CostCode::factory()->create([
        'project_id' => $project->id,
        'code' => '03300',
        'description' => 'Concrete',
    ]);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'cost_code_id' => $costCode->id,
        'date' => now()->startOfWeek()->addDay()->toDateString(),
        'regular_hours' => 8,
        'overtime_hours' => 2,
        'double_time_hours' => 1,
        'work_classification' => 'Journeyman Electrician',
        'prevailing_base_rate' => 50,
        'prevailing_fringe_rate' => 15,
    ]);

    $this->actingAs($user);

    Livewire::test(CertifiedPayrollIndex::class)
        ->set('projectId', $project->id)
        ->set('weekStarting', now()->startOfWeek()->toDateString())
        ->assertSee('Journeyman Electrician')
        ->assertSee('PRJ-8001 - '.$project->name)
        ->assertSee('03300 - Concrete')
        ->assertSee('650.00');
});

it('exports certified payroll report csv with payroll export permission', function (): void {
    $user = payrollReportsUserWithPermissions(['payroll-reports.view', 'payroll-reports.export']);

    $project = Project::factory()->create();
    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'date' => now()->startOfWeek()->toDateString(),
        'regular_hours' => 8,
        'prevailing_base_rate' => 40,
    ]);

    $this->actingAs($user);

    $weekStart = now()->startOfWeek()->toDateString();

    Livewire::test(CertifiedPayrollIndex::class)
        ->set('projectId', $project->id)
        ->set('weekStarting', $weekStart)
        ->call('exportCsv')
        ->assertFileDownloaded('certified-payroll-wh347-'.$weekStart.'.csv');
});

it('forbids certified payroll export without payroll export permission', function (): void {
    $user = payrollReportsUserWithPermissions(['payroll-reports.view']);

    $this->actingAs($user);

    Livewire::test(CertifiedPayrollIndex::class)
        ->call('exportCsv')
        ->assertForbidden();
});

it('renders labor cost report rows and exports csv', function (): void {
    $user = payrollReportsUserWithPermissions(['payroll-reports.view', 'payroll-reports.export']);

    $project = Project::factory()->create([
        'project_number' => 'PRJ-7001',
    ]);

    $costCode = CostCode::factory()->create([
        'project_id' => $project->id,
        'code' => '26000',
        'description' => 'Electrical',
    ]);

    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'cost_code_id' => $costCode->id,
        'date' => now()->toDateString(),
        'regular_hours' => 8,
        'overtime_hours' => 1,
        'prevailing_base_rate' => 60,
    ]);

    $this->actingAs($user);

    Livewire::test(LaborCostIndex::class)
        ->set('projectId', $project->id)
        ->assertSee('PRJ-7001 - '.$project->name)
        ->assertSee('26000 - Electrical')
        ->assertSee('570.00')
        ->call('exportCsv')
        ->assertFileDownloaded('payroll-labor-cost-'.Str::slug(nowDateRangeLabel()).'.csv');
});

it('renders union remittance rows and exports csv', function (): void {
    $user = payrollReportsUserWithPermissions(['payroll-reports.view', 'payroll-reports.export']);

    $profile = PayrollEmployeeProfile::factory()->create([
        'user_id' => $user->id,
        'union_code' => 'IBEW-11',
    ]);

    $payRun = PayRun::factory()->create([
        'pay_period_start' => now()->subWeek()->startOfWeek()->toDateString(),
        'pay_period_end' => now()->subWeek()->endOfWeek()->toDateString(),
        'pay_date' => now()->toDateString(),
        'created_by' => $user->id,
    ]);

    PayrollStatement::factory()->create([
        'user_id' => $user->id,
        'payroll_employee_profile_id' => $profile->id,
        'pay_run_id' => $payRun->id,
        'gross_pay' => 2000,
        'total_regular_hours' => 40,
        'total_ot_hours' => 4,
        'total_dt_hours' => 0,
    ]);

    $deduction = Deduction::factory()->create([
        'name' => 'Union Dues',
        'category' => 'union',
        'calculation_method' => 'percentage',
        'amount' => 2.5,
    ]);

    EmployeeDeduction::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'deduction_id' => $deduction->id,
        'effective_date' => now()->subMonth()->toDateString(),
        'status' => 'active',
    ]);

    $this->actingAs($user);

    $from = now()->subMonthsNoOverflow(1)->startOfMonth()->toDateString();
    $to = now()->toDateString();

    Livewire::test(UnionRemittanceIndex::class)
        ->set('unionCode', 'IBEW-11')
        ->set('fromDate', $from)
        ->set('toDate', $to)
        ->assertSee('IBEW-11')
        ->assertSee('Union Dues')
        ->assertSee('50.00')
        ->call('exportCsv')
        ->assertFileDownloaded('union-remittance-ibew-11-'.$from.'-to-'.$to.'.csv');
});

/**
 * @param  array<int, string>  $permissions
 */
if (! function_exists('payrollReportsUserWithPermissions')) {
    function payrollReportsUserWithPermissions(array $permissions): User
    {
        app(DomainPermissionSynchronizer::class)->sync();

        $user = User::factory()->create(['is_admin' => false]);

        $role = Role::query()->create([
            'name' => 'Payroll Reports Test Role '.str()->uuid(),
            'description' => 'Role created by payroll reports tests',
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

if (! function_exists('nowDateRangeLabel')) {
    function nowDateRangeLabel(): string
    {
        return now()->startOfMonth()->toDateString().'-to-'.now()->toDateString();
    }
}
