<?php

use App\Core\Announcement\Providers\AnnouncementServiceProvider;
use App\Core\Assets\Providers\AssetsServiceProvider;
use App\Core\Audit\Providers\AuditServiceProvider;
use App\Core\Auth\Permission\Providers\PermissionServiceProvider;
use App\Core\Auth\Role\Providers\RoleServiceProvider;
use App\Core\Auth\User\Providers\AuthUserServiceProvider;
use App\Core\Identity\Providers\FortifyServiceProvider;
use App\Core\Identity\Providers\UserServiceProvider;
use App\Core\Notification\Channels\EmailNotification\Providers\EmailServiceProvider;
use App\Core\Notification\Channels\PushNotification\Providers\PushServiceProvider;
use App\Core\Notification\Providers\NotificationServiceProvider;
use App\Core\Queue\Providers\QueueManagerServiceProvider;
use App\Core\Scheduler\Providers\SchedulerServiceProvider;
use App\Core\Settings\Providers\SettingServiceProvider;
use App\Core\UI\Dashboard\Providers\DashboardServiceProvider;
use App\Core\UI\Navigation\Providers\NavigationServiceProvider;
use App\Domains\Providers\DomainServiceProvider;
use App\PlugIns\Cpanel\Providers\CpanelServiceProvider;
use App\PlugIns\WeatherApi\Providers\WeatherApiServiceProvider;
use App\PlugIns\Zoom\Providers\ZoomServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\VoltServiceProvider;

return [
    AnnouncementServiceProvider::class,
    AuditServiceProvider::class,
    PermissionServiceProvider::class,
    RoleServiceProvider::class,
    AuthUserServiceProvider::class,
    CpanelServiceProvider::class,
    DashboardServiceProvider::class,
    AssetsServiceProvider::class,
    FortifyServiceProvider::class,
    UserServiceProvider::class,
    NavigationServiceProvider::class,
    NotificationServiceProvider::class,
    EmailServiceProvider::class,
    PushServiceProvider::class,
    QueueManagerServiceProvider::class,
    SchedulerServiceProvider::class,
    SettingServiceProvider::class,
    WeatherApiServiceProvider::class,
    ZoomServiceProvider::class,
    DomainServiceProvider::class,
    AppServiceProvider::class,
    TelescopeServiceProvider::class,
    VoltServiceProvider::class,
];
