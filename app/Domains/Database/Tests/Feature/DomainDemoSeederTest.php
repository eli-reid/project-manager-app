<?php

use App\Core\Identity\Models\User;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Documents\Models\Document;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Projects\Models\Project;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Timecards\Models\Timecard;
use Database\Seeders\DatabaseSeeder;

it('seeds demo domain data for end-to-end app walkthroughs and reporting', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Project::query()->count())->toBeGreaterThanOrEqual(10);
    expect(Invoice::query()->count())->toBeGreaterThanOrEqual(30);
    expect(StockOrder::query()->count())->toBeGreaterThanOrEqual(20);
    expect(Document::query()->count())->toBeGreaterThanOrEqual(30);

    expect(User::query()->where('is_active', true)->count())->toBeGreaterThanOrEqual(17);
    expect(Timecard::query()->count())->toBeGreaterThan(0);
    expect(DailyReport::query()->count())->toBeGreaterThan(0);

    expect(PayrollEmployeeProfile::query()->count())->toBeGreaterThan(0);
    expect(PayRun::query()->count())->toBeGreaterThan(0);
    expect(PayrollStatement::query()->count())->toBeGreaterThan(0);
});
