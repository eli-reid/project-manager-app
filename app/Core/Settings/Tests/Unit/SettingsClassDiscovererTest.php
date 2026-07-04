<?php

use App\Core\Notification\Settings\NotificationSettings;
use App\Core\Settings\Services\SettingsClassDiscoverer;
use App\Domains\PushNotification\Settings\PushNotificationSettings;
use App\PlugIns\WeatherApi\Settings\WeatherApiSettings;

it('discovers settings classes from core, domain, and plug-in folders', function (): void {
    $classes = app(SettingsClassDiscoverer::class)->discover();

    expect($classes)
        ->toContain(NotificationSettings::class)
        ->toContain(PushNotificationSettings::class)
        ->toContain(WeatherApiSettings::class);
});
