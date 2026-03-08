<?php

return [
    App\Domains\Providers\DomainServiceProvider::class,
    App\Core\Settings\Providers\SettingServiceProvider::class,
    App\Core\User\Providers\FortifyServiceProvider::class,
    App\Core\User\Providers\UserServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\TelescopeServiceProvider::class,
];
