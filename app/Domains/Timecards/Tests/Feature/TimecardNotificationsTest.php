<?php

use App\Core\Auth\Permission\Models\Permission;
use App\Core\Auth\Permission\Services\DomainPermissionSynchronizer;
use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Core\Notification\Settings\NotificationSettings;
use App\Core\Settings\Facades\Settings;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Notifications\MissingTimecardReminder;
use App\Domains\Timecards\Notifications\TimecardApprovedNotification;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;
use App\Domains\Timecards\Notifications\TimecardRejectedNotification;
use App\Domains\Timecards\Notifications\TimecardReminderDigestNotification;
use App\Domains\Timecards\Notifications\TimecardSubmittedNotification;
use App\Domains\Timecards\Services\TimecardLifecycleService;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

it('sends submitted notifications to users with timecards approve permission', function (): void {
    Notification::fake();

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

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

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

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

    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database"]');

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

it('uses admin-configured allowed channels for timecard approval notifications', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["mail", "database", "sms"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED), '["database"]');

    $owner = User::factory()->create(['is_admin' => false]);
    $timecard = Timecard::factory()->create([
        'user_id' => $owner->id,
        'status' => Timecard::STATUS_SUBMITTED,
    ]);

    $channels = (new TimecardApprovedNotification($timecard))->via($owner);

    expect($channels)->toBe(['database']);
});

it('stores database notifications with uuid notification ids for ulid users', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["database"]');

    $owner = User::factory()->create(['is_admin' => false]);
    $approver = User::factory()->create(['is_admin' => false]);

    $timecard = Timecard::factory()->create([
        'user_id' => $owner->id,
        'status' => Timecard::STATUS_SUBMITTED,
    ]);

    app(TimecardLifecycleService::class)->approve($timecard, $approver);

    $notification = DatabaseNotification::query()
        ->where('notifiable_id', $owner->id)
        ->latest('created_at')
        ->first();

    expect($notification)->not->toBeNull()
        ->and(strlen((string) $notification?->id))->toBe(36)
        ->and($notification?->type)->toBe(TimecardApprovedNotification::class);
});

it('includes push as a supported channel for all timecard notification definitions', function (): void {
    $definitions = collect(TimecardNotificationDefinitions::definitions())->keyBy('key');

    expect($definitions[TimecardNotificationDefinitions::APPROVED]['supported_channels'])
        ->toContain('push')
        ->and($definitions[TimecardNotificationDefinitions::SUBMITTED]['supported_channels'])
        ->toContain('push')
        ->and($definitions[TimecardNotificationDefinitions::REJECTED]['supported_channels'])
        ->toContain('push')
        ->and($definitions[TimecardNotificationDefinitions::REMINDER]['supported_channels'])
        ->toContain('push')
        ->and($definitions[TimecardNotificationDefinitions::MISSING_REMINDER]['supported_channels'])
        ->toContain('push');
});

it('builds push payloads for timecard lifecycle notifications', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $timecard = Timecard::factory()->create([
        'user_id' => $user->id,
        'status' => Timecard::STATUS_SUBMITTED,
        'week_ending' => '2026-05-24',
        'rejection_reason' => 'Missing notes',
    ]);

    $approvedPayload = (new TimecardApprovedNotification($timecard))->toWebPush($user, new TimecardApprovedNotification($timecard))->toArray();
    $submittedPayload = (new TimecardSubmittedNotification($timecard))->toWebPush($user, new TimecardSubmittedNotification($timecard))->toArray();
    $rejectedPayload = (new TimecardRejectedNotification($timecard))->toWebPush($user, new TimecardRejectedNotification($timecard))->toArray();

    expect($approvedPayload['title'])->toBe('Timecard approved')
        ->and($approvedPayload['data']['timecard_id'])->toBe((string) $timecard->id)
        ->and($submittedPayload['title'])->toBe('Timecard submitted')
        ->and($submittedPayload['data']['timecard_id'])->toBe((string) $timecard->id)
        ->and($rejectedPayload['title'])->toBe('Timecard rejected')
        ->and($rejectedPayload['data']['rejection_reason'])->toBe('Missing notes');
});

it('resolves push for reminder digest notifications when enabled', function (): void {
    Settings::set('notifications.enabled', 'true');
    Settings::set('notifications.default_channels', '["push"]');
    Settings::set(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::REMINDER), '["push"]');

    $user = User::factory()->create(['is_admin' => false]);
    $notification = new TimecardReminderDigestNotification(collect(), now()->toDateString());
    $channels = $notification->via($user);

    expect($channels)->toBe([WebPushChannel::class]);
});

it('builds push payloads for reminder notifications', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $digestNotification = new TimecardReminderDigestNotification(collect(), '2026-05-24');
    $digestMessage = $digestNotification->toWebPush($user, $digestNotification);

    $missingNotification = new MissingTimecardReminder(CarbonImmutable::parse('2026-05-18'));
    $missingMessage = $missingNotification->toWebPush($user, $missingNotification);

    expect($digestMessage)->toBeInstanceOf(WebPushMessage::class)
        ->and($missingMessage)->toBeInstanceOf(WebPushMessage::class);

    $digestPayload = $digestMessage->toArray();
    $missingPayload = $missingMessage->toArray();

    expect($digestPayload)
        ->toMatchArray([
            'title' => 'Timecard reminder',
            'tag' => 'timecard-reminder-2026-05-24',
        ])
        ->and($digestPayload['data']['url'])->toBe(route('timecards.index'))
        ->and($digestPayload['body'])->toContain('week ending 2026-05-24')
        ->and($missingPayload)
        ->toMatchArray([
            'title' => 'Missing timecard',
            'tag' => 'timecard-missing-2026-05-24',
        ])
        ->and($missingPayload['data']['url'])->toBe(route('timecards.create'))
        ->and($missingPayload['body'])->toContain('week ending 2026-05-24');
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
