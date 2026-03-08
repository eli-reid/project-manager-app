<?php

namespace App\Core\Cpanel\Providers;

use App\Core\Cpanel\Data\CpanelConfig;
use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Cpanel\Services\CpanelService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CpanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CpanelConfig::class, function (): CpanelConfig {
            return CpanelConfig::fromServicesConfig(config('services.cpanel'));
        });

        $this->app->singleton(CpanelService::class, function ($app): CpanelService {
            return new CpanelService($app->make(CpanelConfig::class));
        });

        $this->app->singleton(CpanelMailboxManager::class, function ($app): CpanelMailboxManager {
            return new CpanelMailboxManager($app->make(CpanelService::class));
        });
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth', 'can:admin'])
            ->group(__DIR__.'/../Routes/admin.php');
    }
}
