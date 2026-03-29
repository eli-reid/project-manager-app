<?php

use App\Core\User\Models\Permission;
use App\Core\User\Models\Role;
use App\Core\User\Models\User;
use App\Core\User\Services\DomainPermissionSynchronizer;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Notifications\TimecardApprovedNotification;
use App\Domains\Timecards\Notifications\TimecardRejectedNotification;
use App\Domains\Timecards\Notifications\TimecardSubmittedNotification;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Illuminate\Support\Facades\Notification;

it('sends submitted notifications to users with timecards approve permission', function (): void {
    Notification::fake();

    settings()->set('notifications.enabled', 'true');
    settings()->set('notifications.default_channels', '["mail", "database"]');

    $submitter = User::factory()->create(['is_admin' => false]);
    $approver = userWithNotificationPermission('timecards.approve');
    $nonApprover = User::factory()->create(['is_admin' => false]);

    $service = app(TimecardLifecycleService::class);
    $timecard = $service->createDraftForUser($submitter, '2026-04-12');
    $timecard->entries()->create([
        'user_id' => $submitter->id,
        'project_id' => null,
        'custom_project_name' => 'Field Work',
        'date' => '2026-04-14',
        'start_time' => '07:00:00',
        'hours' => 8,
        'notes' => 'Worked onsite',
    ]);

    $service->submit($timecard);

    Notification::assertSentTo($approver, TimecardSubmittedNotification::class);
    Notification::assertNotSentTo($submitter, TimecardSubmittedNotification::class);
    Notification::assertNotSentTo($nonApprover, TimecardSubmittedNotification::class);
});

it('sends approved notification to the timecard owner', function (): void {
    Notification::fake();

    settings()->set('notifications.enabled', 'true');
    settings()->set('notifications.default_channels', '["mail", "database"]');

    $owner = User::factory()->create(['is_admin' => false]);
    $approver = User::factory()->create(['is_admin' => false]);

    $timecard = Timecard::factory()->create([
        'user_id' => $owner->id,
        'status' => Timecard::STATUS_SUBMITTED,
    ]);

    app(TimecardLifecycleService::class)->approve($timecard, $approver);

    Notification::assertSentTo($owner, TimecardApprovedNotification::class);
    Notification::assertNotSentTo($approver, TimecardApprovedNotification::class);
});

it('sends rejected notification with reason to the timecard owner', function (): void {
    Notification::fake();

    settings()->set('notifications.enabled', 'true');
    settings()->set('notifications.default_channels', '["mail", "database"]');

    $owner = User::factory()->create(['is_admin' => false]);
    $rejector = User::factory()->create(['is_admin' => false]);

    $timecard = Timecard::factory()->create([
        'user_id' => $owner->id,
        'status' => Timecard::STATUS_SUBMITTED,
    ]);

    app(TimecardLifecycleService::class)->reject($timecard, $rejector, 'Missing notes');

    Notification::assertSentTo($owner, TimecardRejectedNotification::class, function (TimecardRejectedNotification $notification): bool {
        return $notification->timecard->rejection_reason === 'Missing notes';
    });
});

function userWithNotificationPermission(string $permissionKey): User
{
    app(DomainPermissionSynchronizer::class)->sync();

    $user = User::factory()->create(['is_admin' => false]);
    $role = Role::query()->create([
        'name' => 'Notification Test Role '.str()->uuid(),
        'description' => 'Role for notification tests',
        'is_active' => true,
        'built_in' => false,
        'access_level' => 10,
    ]);

    [$resource, $action] = explode('.', $permissionKey, 2);
    $permissionId = Permission::query()
        ->where('resource', $resource)
        ->where('action', $action)
        ->value('id');

    if ($permissionId !== null) {
        $role->permissions()->sync([$permissionId]);
    }

    $user->roles()->sync([$role->id]);

    return $user->fresh();
}
