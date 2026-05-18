<?php

namespace App\Domains\Payroll\Tests\Feature;

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayrollReportsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function payroll_reports_index_redirects_to_central_reports_index(): void
    {
        $user = $this->payrollReportsUserWithPermissions(['payroll-runs.preview']);

        $this->actingAs($user)
            ->get(route('admin.payroll.reports.index'))
            ->assertRedirect(route('admin.reports.index'));
    }

    #[Test]
    public function user_without_payroll_permission_cannot_access_reports_index(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.payroll.reports.index'))
            ->assertForbidden();
    }

    #[Test]
    public function reports_index_contains_link_to_weekly_employee_hours(): void
    {
        $user = $this->payrollReportsUserWithPermissions(['payroll-runs.preview', 'reports.operational.view']);

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertSee(route('admin.payroll.reports.weekly-employee-hours'));
    }

    #[Test]
    public function weekly_employee_hours_uses_payroll_layout_when_referred_from_payroll_pages(): void
    {
        $user = $this->payrollReportsUserWithPermissions(['payroll-runs.preview']);

        $this->actingAs($user)
            ->get(route('admin.payroll.reports.weekly-employee-hours'), [
                'HTTP_REFERER' => route('admin.payroll.runs.index'),
            ])
            ->assertSuccessful()
            ->assertSee('Timecards')
            ->assertSee('Runs');
    }

    #[Test]
    public function weekly_hour_adjustments_uses_app_layout_when_referred_from_reports_pages(): void
    {
        $user = $this->payrollReportsUserWithPermissions(['payroll-runs.preview']);

        $this->actingAs($user)
            ->get(route('admin.payroll.reports.weekly-hour-adjustments'), [
                'HTTP_REFERER' => route('admin.reports.index'),
            ])
            ->assertSuccessful()
            ->assertDontSee('Timecards')
            ->assertDontSee('Runs');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function payrollReportsUserWithPermissions(array $permissions): User
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
