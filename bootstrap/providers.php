<?php

return [
    App\Domains\Providers\DomainServiceProvider::class,
    App\Core\Cpanel\Providers\CpanelServiceProvider::class,
    App\Core\Scheduler\Providers\SchedulerServiceProvider::class,
    App\Core\WeatherApi\Providers\WeatherApiServiceProvider::class,
    App\Core\Settings\Providers\SettingServiceProvider::class,
    App\Core\User\Providers\FortifyServiceProvider::class,
    App\Core\User\Providers\UserServiceProvider::class,
    App\Core\Announcement\Providers\AnnouncementServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
];
