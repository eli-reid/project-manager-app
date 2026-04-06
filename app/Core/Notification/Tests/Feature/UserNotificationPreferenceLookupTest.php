<?php

use App\Core\Identity\Models\User;
use App\Core\Notification\Models\UserNotificationPreference;
use App\Core\Notification\Services\NotificationPreferenceService;

it('returns null when no stored user notification preference exists', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $preference = app(NotificationPreferenceService::class)->notificationPreferenceFor(
        $user,
        'timecards.approved',
        'mail',
    );

    expect($preference)->toBeNull();
});

it('returns stored user notification preference value', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    UserNotificationPreference::query()->create([
        'user_id' => $user->id,
        'notification_key' => 'timecards.approved',
        'channel' => 'mail',
        'enabled' => false,
    ]);

    $preference = app(NotificationPreferenceService::class)->notificationPreferenceFor(
        $user,
        'timecards.approved',
        'mail',
    );

    expect($preference)->toBeFalse();
});

it('delegates user notificationPreferenceFor to notification service', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    UserNotificationPreference::query()->create([
        'user_id' => $user->id,
        'notification_key' => 'timecards.approved',
        'channel' => 'database',
        'enabled' => true,
    ]);

    expect($user->notificationPreferenceFor('timecards.approved', 'database'))->toBeTrue();
});
