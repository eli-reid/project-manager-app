<?php

use App\Providers\AppServiceProvider;
use App\Core\User\Providers\FortifyServiceProvider;
use App\Core\User\Providers\UserRouteServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    UserRouteServiceProvider::class,
];
