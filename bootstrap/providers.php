<?php

use App\Core\Settings\Providers\SettingServiceProvider;
use App\Core\User\Providers\FortifyServiceProvider;
use App\Core\User\Providers\UserServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    SettingServiceProvider::class,
    FortifyServiceProvider::class,
    UserServiceProvider::class,
];
