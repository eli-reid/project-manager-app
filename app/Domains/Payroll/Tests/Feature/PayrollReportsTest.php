<?php

namespace App\Domains\Payroll\Tests\Feature;

use App\Domains\Payroll\Models\PayRun;
use App\Core\Identity\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayrollReportsTest extends TestCase
{
    #[Test]
    public function payroll_reports_index_redirects_to_central_reports_index(): void
    {
        $user = User::factory()
            ->has(PayRun::factory()->count(1))
            ->create();
        $user->givePermissionTo('payroll-runs.preview');

        $this->actingAs($user)
            ->get(route('admin.payroll.reports.index'))
            ->assertRedirect(route('admin.reports.index'));
    }

    #[Test]
    public function user_without_payroll_permission_cannot_access_reports_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.payroll.reports.index'))
            ->assertForbidden();
    }

    #[Test]
    public function reports_index_contains_link_to_weekly_employee_hours(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('payroll-runs.preview');
        $user->givePermissionTo('reports.operational.view');

        $this->actingAs($user)
            ->get(route('admin.reports.index'))
            ->assertSee(route('admin.payroll.reports.weekly-employee-hours'));
    }
}
