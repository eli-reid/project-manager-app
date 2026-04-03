<?php

use App\Core\Notification\Support\NotificationEventCatalog;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;

it('returns unique notification event keys', function (): void {
    $keys = NotificationEventCatalog::keys();

    expect($keys)
        ->toBeArray()
        ->and($keys)
        ->toHaveCount(count(array_unique($keys)));
});

it('contains core timecard notification events', function (): void {
    $keys = NotificationEventCatalog::keys();

    expect($keys)
        ->toContain(TimecardNotificationDefinitions::APPROVED)
        ->toContain(TimecardNotificationDefinitions::SUBMITTED)
        ->toContain(TimecardNotificationDefinitions::REJECTED)
        ->toContain(TimecardNotificationDefinitions::REMINDER);
});

it('can check registration of known and unknown event keys', function (): void {
    expect(NotificationEventCatalog::isRegistered(TimecardNotificationDefinitions::APPROVED))->toBeTrue()
        ->and(NotificationEventCatalog::isRegistered('unknown.event'))->toBeFalse();
});
