<?php

use App\Core\Identity\Models\User;

it('shows payroll report links in payroll admin navbar when user can preview payroll runs', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $this->actingAs($user)
        ->get(route('admin.payroll.timecards.review'))
        ->assertSuccessful()
        ->assertSee(route('admin.payroll.reports.weekly-employee-hours'))
        ->assertSee(route('admin.payroll.reports.weekly-hour-adjustments'));
});
