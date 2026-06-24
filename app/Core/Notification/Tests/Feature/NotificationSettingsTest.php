<?php

use App\Core\Notification\Settings\NotificationSettings;
use App\Core\Settings\Services\DomainSettingsSynchronizer;
use App\Domains\Projects\Notifications\ProjectNotificationDefinitions;
use App\Domains\Tasks\Notifications\TaskNotificationDefinitions;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;

it('defines admin allowed channel settings for domain notification types', function (): void {
    $keys = collect(NotificationSettings::definitions())
        ->pluck('key')
        ->all();

    expect($keys)
        ->toContain(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED))
        ->toContain(NotificationSettings::allowedChannelsSettingKey(ProjectNotificationDefinitions::ACCESS_GRANTED))
        ->toContain(NotificationSettings::allowedChannelsSettingKey(TaskNotificationDefinitions::ASSIGNED));
});

it('loads notification channel settings definitions through the domain settings synchronizer', function (): void {
    $definitions = app(DomainSettingsSynchronizer::class)->loadDefinitions();
    $keys = collect($definitions)->pluck('key')->all();

    expect($keys)
        ->toContain(NotificationSettings::allowedChannelsSettingKey(TimecardNotificationDefinitions::APPROVED))
        ->toContain(NotificationSettings::allowedChannelsSettingKey(TaskNotificationDefinitions::STATUS_UPDATED));
});
