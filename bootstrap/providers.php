<?php

use App\Core\Announcement\Providers\AnnouncementServiceProvider;
use App\Core\Audit\Providers\AuditServiceProvider;
use App\Core\Auth\Permission\Providers\PermissionServiceProvider;
use App\Core\Auth\Role\Providers\RoleServiceProvider;
use App\Core\Auth\User\Providers\AuthUserServiceProvider;
use App\Core\Cpanel\Providers\CpanelServiceProvider;
use App\Core\Notification\Providers\NotificationServiceProvider;
use App\Core\Queue\Providers\QueueManagerServiceProvider;
use App\Core\Scheduler\Providers\SchedulerServiceProvider;
use App\Core\Settings\Providers\SettingServiceProvider;
use App\Core\User\Providers\FortifyServiceProvider;
use App\Core\User\Providers\UserServiceProvider;
use App\Core\WeatherApi\Providers\WeatherApiServiceProvider;
use App\Domains\Providers\DomainServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AnnouncementServiceProvider::class,
    AuditServiceProvider::class,
    CpanelServiceProvider::class,
    NotificationServiceProvider::class,
    QueueManagerServiceProvider::class,
    SchedulerServiceProvider::class,
    SettingServiceProvider::class,
    PermissionServiceProvider::class,
    RoleServiceProvider::class,
    AuthUserServiceProvider::class,
    FortifyServiceProvider::class,
    UserServiceProvider::class,
    WeatherApiServiceProvider::class,
    DomainServiceProvider::class,
    AppServiceProvider::class,
    TelescopeServiceProvider::class,
];
