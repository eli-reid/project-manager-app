<?php

use App\Providers\AppServiceProvider;
use App\Core\User\Providers\FortifyServiceProvider;
use App\Core\User\Providers\UserServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    UserServiceProvider::class,
];
