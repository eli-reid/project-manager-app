<?php

use App\Core\Identity\Models\User;
use App\Core\Notification\Settings\NotificationSettings;
use App\Core\Notification\Support\NotificationEventCatalog;
use App\Core\Settings\Facades\Settings;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayRateType;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Notifications\PayrollNotificationDefinitions;
use App\Domains\Payroll\Notifications\PayRunApprovedNotification;
use App\Domains\Payroll\Notifications\PayRunFinalizedNotification;
use App\Domains\Payroll\Notifications\PayRunVoidedNotification;
use App\Domains\Payroll\Notifications\PayStubAvailableNotification;
use App\Domains\Payroll\Services\PayRunService;
use App\Domains\Projects\Models\Project;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

it('registers payroll notification definitions in the notification registry', function (): void {
    $keys = NotificationEventCatalog::keys();

    expect($keys)
        ->toContain(PayrollNotificationDefinitions::APPROVED)
        ->toContain(PayrollNotificationDefinitions::FINALIZED)
        ->toContain(PayrollNotificationDefinitions::VOIDED)
        ->toContain(PayrollNotificationDefinitions::PAY_STUB_AVAILABLE)
        ->toContain(PayrollNotificationDefinitions::RATE_CHANGE_EFFECTIVE)
        ->toContain(PayrollNotificationDefinitions::DEDUCTION_MODIFIED)
        ->toContain(PayrollNotificationDefinitions::TAX_TABLE_UPDATE_AVAILABLE)
        ->toContain(PayrollNotificationDefinitions::HASH_CHAIN_INTEGRITY_FAILURE)
        ->toContain(PayrollNotificationDefinitions::CERTIFIED_PAYROLL_DUE)
        ->toContain(PayrollNotificationDefinitions::CERTIFIED_PAYROLL_GENERATED)
        ->toContain(PayrollNotificationDefinitions::QUARTERLY_TAX_FILING_DUE);
});

it('sends approved notification when pay run is approved', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $creator = User::factory()->create(['is_admin' => false]);
    $approver = User::factory()->create(['is_admin' => false]);
    $worker = User::factory()->create(['is_admin' => false]);

    $profile = PayrollEmployeeProfile::factory()->create([
        'user_id' => $worker->id,
    ]);

    $standardType = PayRateType::factory()->standard()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $standardType->id,
        'rate_amount' => 55,
        'effective_date' => now()->subMonth()->toDateString(),
        'approved_by' => $creator->id,
    ]);

    $project = Project::factory()->create();

    $timecard = Timecard::factory()->create([
        'user_id' => $worker->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $worker->id,
        'project_id' => $project->id,
        'date' => now()->startOfWeek()->addDay()->toDateString(),
        'hours' => 8,
        'regular_hours' => 8,
        'overtime_hours' => 0,
        'double_time_hours' => 0,
    ]);

    $payRunService = app(PayRunService::class);

    $payRun = $payRunService->createPreview(
        now()->startOfWeek(),
        now()->endOfWeek(),
        now()->endOfWeek()->addWeek(),
        $creator->id,
    );

    $payRun = $payRunService->approve($payRun, $approver->id);
    $payRunId = (string) $payRun->id;

    Notification::assertSentTo($approver, PayRunApprovedNotification::class, function (PayRunApprovedNotification $notification) use ($payRunId): bool {
        return $notification->payRunId === $payRunId;
    });
});

it('sends finalized notification when pay run is finalized', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

    $creator = User::factory()->create(['is_admin' => false]);
    $approver = User::factory()->create(['is_admin' => false]);
    $worker = User::factory()->create(['is_admin' => false]);

    $profile = PayrollEmployeeProfile::factory()->create([
        'user_id' => $worker->id,
    ]);

    $standardType = PayRateType::factory()->standard()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $standardType->id,
        'rate_amount' => 55,
        'effective_date' => now()->subMonth()->toDateString(),
        'approved_by' => $creator->id,
    ]);

    $project = Project::factory()->create();

    $timecard = Timecard::factory()->create([
        'user_id' => $worker->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $worker->id,
        'project_id' => $project->id,
        'date' => now()->startOfWeek()->addDay()->toDateString(),
        'hours' => 8,
        'regular_hours' => 8,
        'overtime_hours' => 0,
        'double_time_hours' => 0,
    ]);

    $payRunService = app(PayRunService::class);

    $payRun = $payRunService->createPreview(
        now()->startOfWeek(),
        now()->endOfWeek(),
        now()->endOfWeek()->addWeek(),
        $creator->id,
    );

    $payRun = $payRunService->approve($payRun, $approver->id);
    Notification::assertSentTo($approver, PayRunApprovedNotification::class);

    // Start fresh for finalization notification test
    $payRunIdFinal = (string) $payRun->id;
    $payRun = $payRunService->finalize($payRun);

    Notification::assertSentTo($approver, PayRunFinalizedNotification::class, function (PayRunFinalizedNotification $notification) use ($payRunIdFinal): bool {
        return $notification->payRunId === $payRunIdFinal && $notification->employeeCount === 1;
    });
});

it('sends voided notification when finalized pay run is voided', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database", "sms"]');

    $creator = User::factory()->create(['is_admin' => false]);
    $approver = User::factory()->create(['is_admin' => false]);
    $worker = User::factory()->create(['is_admin' => false]);

    $profile = PayrollEmployeeProfile::factory()->create([
        'user_id' => $worker->id,
    ]);

    $standardType = PayRateType::factory()->standard()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $standardType->id,
        'rate_amount' => 55,
        'effective_date' => now()->subMonth()->toDateString(),
        'approved_by' => $creator->id,
    ]);

    $project = Project::factory()->create();

    $timecard = Timecard::factory()->create([
        'user_id' => $worker->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $worker->id,
        'project_id' => $project->id,
        'date' => now()->startOfWeek()->addDay()->toDateString(),
        'hours' => 8,
        'regular_hours' => 8,
        'overtime_hours' => 0,
        'double_time_hours' => 0,
    ]);

    $payRunService = app(PayRunService::class);

    $payRun = $payRunService->createPreview(
        now()->startOfWeek(),
        now()->endOfWeek(),
        now()->endOfWeek()->addWeek(),
        $creator->id,
    );

    $payRun = $payRunService->approve($payRun, $approver->id);
    $payRun = $payRunService->finalize($payRun);

    $payRunIdVoid = (string) $payRun->id;
    $payRun = $payRunService->voidRun($payRun);

    Notification::assertSentTo($approver, PayRunVoidedNotification::class, function (PayRunVoidedNotification $notification) use ($payRunIdVoid): bool {
        return $notification->payRunId === $payRunIdVoid;
    });
});

it('uses admin-configured allowed channels for approved notification', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["database", "push"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(PayrollNotificationDefinitions::APPROVED), '["database", "push"]');

    $creator = User::factory()->create(['is_admin' => false]);
    $approver = User::factory()->create(['is_admin' => false]);
    $worker = User::factory()->create(['is_admin' => false]);

    $profile = PayrollEmployeeProfile::factory()->create([
        'user_id' => $worker->id,
    ]);

    $standardType = PayRateType::factory()->standard()->create();

    PayRate::factory()->create([
        'payroll_employee_profile_id' => $profile->id,
        'pay_rate_type_id' => $standardType->id,
        'rate_amount' => 55,
        'effective_date' => now()->subMonth()->toDateString(),
        'approved_by' => $creator->id,
    ]);

    $project = Project::factory()->create();

    $timecard = Timecard::factory()->create([
        'user_id' => $worker->id,
        'status' => Timecard::STATUS_APPROVED,
    ]);

    TimecardEntry::factory()->create([
        'timecard_id' => $timecard->id,
        'user_id' => $worker->id,
        'project_id' => $project->id,
        'date' => now()->startOfWeek()->addDay()->toDateString(),
        'hours' => 8,
        'regular_hours' => 8,
        'overtime_hours' => 0,
        'double_time_hours' => 0,
    ]);

    $payRunService = app(PayRunService::class);

    $payRun = $payRunService->createPreview(
        now()->startOfWeek(),
        now()->endOfWeek(),
        now()->endOfWeek()->addWeek(),
        $creator->id,
    );

    $payRun = $payRunService->approve($payRun, $approver->id);

    $notification = new PayRunApprovedNotification(
        payRunId: (string) $payRun->id,
        approvedBy: $approver->username,
        payPeriodStart: $payRun->pay_period_start->toDateString(),
        payPeriodEnd: $payRun->pay_period_end->toDateString(),
    );

    $channels = $notification->via($approver);

    expect($channels)
        ->toContain('database')
        ->toContain(WebPushChannel::class);
});

it('builds push payloads for payroll notifications', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $approved = new PayRunApprovedNotification(
        payRunId: 'PR-100',
        approvedBy: 'jane.manager',
        payPeriodStart: '2026-05-01',
        payPeriodEnd: '2026-05-15',
    );

    $finalized = new PayRunFinalizedNotification(
        payRunId: 'PR-100',
        payPeriodStart: '2026-05-01',
        payPeriodEnd: '2026-05-15',
        payDate: '2026-05-20',
        employeeCount: 12,
    );

    $voided = new PayRunVoidedNotification(
        payRunId: 'PR-100',
        payPeriodStart: '2026-05-01',
        payPeriodEnd: '2026-05-15',
    );

    $payStub = new PayStubAvailableNotification(
        payPeriodEnd: '2026-05-15',
        netPay: '1500.00',
        grossPay: '2200.00',
    );

    expect($approved->toWebPush($user, $approved))->toBeInstanceOf(WebPushMessage::class)
        ->and($finalized->toWebPush($user, $finalized))->toBeInstanceOf(WebPushMessage::class)
        ->and($voided->toWebPush($user, $voided))->toBeInstanceOf(WebPushMessage::class)
        ->and($payStub->toWebPush($user, $payStub))->toBeInstanceOf(WebPushMessage::class);
});

it('registers all payroll notification definitions with correct properties', function (): void {
    $definitions = NotificationEventCatalog::definitions();
    $payrollDefs = collect($definitions)->filter(fn (array $def): bool => str_starts_with($def['key'], 'payroll.'));

    expect($payrollDefs->count())->toBe(14);

    $payrollDefs->each(function (array $def): void {
        expect($def)
            ->toHaveKey('key')
            ->toHaveKey('label')
            ->toHaveKey('description')
            ->toHaveKey('supported_channels');

        expect($def['supported_channels'])->toBeArray()->not->toBeEmpty();
        expect($def['supported_channels'])->each->toBeIn(['mail', 'database', 'sms', 'push']);
    });
});
