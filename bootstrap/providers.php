<?php

use App\Core\Announcement\Providers\AnnouncementServiceProvider;
use App\Core\Assets\Providers\AssetsServiceProvider;
use App\Core\Audit\Providers\AuditServiceProvider;
use App\Core\Auth\Permission\Providers\PermissionServiceProvider;
use App\Core\Auth\Role\Providers\RoleServiceProvider;
use App\Core\Auth\User\Providers\AuthUserServiceProvider;
use App\Core\Cpanel\Providers\CpanelServiceProvider;
use App\Core\Dashboard\Providers\DashboardServiceProvider;
use App\Core\Identity\Providers\FortifyServiceProvider;
use App\Core\Identity\Providers\UserServiceProvider;
use App\Core\Notification\Providers\NotificationServiceProvider;
use App\Core\Queue\Providers\QueueManagerServiceProvider;
use App\Core\Scheduler\Providers\SchedulerServiceProvider;
use App\Core\Settings\Providers\SettingServiceProvider;
use App\Core\WeatherApi\Providers\WeatherApiServiceProvider;
use App\Core\Zoom\Providers\ZoomServiceProvider;
use App\Domains\Providers\DomainServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\VoltServiceProvider;

return [
<<<<<<< HEAD
    AnnouncementServiceProvider::class,
    AssetsServiceProvider::class,
    AuditServiceProvider::class,
    PermissionServiceProvider::class,
    RoleServiceProvider::class,
    AuthUserServiceProvider::class,
    CpanelServiceProvider::class,
    DashboardServiceProvider::class,
    FortifyServiceProvider::class,
    UserServiceProvider::class,
    NotificationServiceProvider::class,
    QueueManagerServiceProvider::class,
    SchedulerServiceProvider::class,
    SettingServiceProvider::class,
    WeatherApiServiceProvider::class,
    ZoomServiceProvider::class,
    DomainServiceProvider::class,
    AppServiceProvider::class,
    TelescopeServiceProvider::class,
    VoltServiceProvider::class,
=======
    App\Core\Announcement\Providers\AnnouncementServiceProvider::class,
    App\Core\Assets\Providers\AssetsServiceProvider::class,
    App\Core\Audit\Providers\AuditServiceProvider::class,
    App\Core\Auth\Permission\Providers\PermissionServiceProvider::class,
    App\Core\Auth\Role\Providers\RoleServiceProvider::class,
    App\Core\Auth\User\Providers\AuthUserServiceProvider::class,
    App\Core\Cpanel\Providers\CpanelServiceProvider::class,
    App\Core\Dashboard\Providers\DashboardServiceProvider::class,
    App\Core\Files\Providers\FilesServiceProvider::class,
    App\Core\Identity\Providers\FortifyServiceProvider::class,
    App\Core\Identity\Providers\UserServiceProvider::class,
    App\Core\Notification\Providers\NotificationServiceProvider::class,
    App\Core\Queue\Providers\QueueManagerServiceProvider::class,
    App\Core\Scheduler\Providers\SchedulerServiceProvider::class,
    App\Core\Settings\Providers\SettingServiceProvider::class,
    App\Core\WeatherApi\Providers\WeatherApiServiceProvider::class,
    App\Core\Zoom\Providers\ZoomServiceProvider::class,
    App\Domains\Providers\DomainServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
>>>>>>> production
];
