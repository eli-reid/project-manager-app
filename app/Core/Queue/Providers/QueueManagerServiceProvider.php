<?php

namespace App\Core\Queue\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Queue\Listeners\RecordQueueJobHistory;
use App\Core\Queue\Permissions\QueuePermissions;
use App\Core\Queue\Policies\QueuePolicy;
use App\Core\Queue\Services\QueueManagerService;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class QueueManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueueManagerService::class, function (): QueueManagerService {
            return new QueueManagerService;
        });
    }

    public function boot(): void
    {
        $this->registerAuthorization();
        $this->registerPermissions();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
        $this->registerEventListeners();
    }

    private function registerAuthorization(): void
    {
        Gate::define('queue.viewAny', fn ($user) => app(QueuePolicy::class)->viewAny($user));
        Gate::define('queue.manage', fn ($user) => app(QueuePolicy::class)->manage($user));
    }

    private function registerPermissions(): void
    {
        /** @var PermissionRegistryContract $registry */
        $registry = $this->app->make(PermissionRegistryContract::class);

        $registry->registerPermissions(QueuePermissions::all());
    }

    private function registerEventListeners(): void
    {
        Event::listen(JobProcessing::class, [RecordQueueJobHistory::class, 'onProcessing']);
        Event::listen(JobProcessed::class, [RecordQueueJobHistory::class, 'onProcessed']);
        Event::listen(JobFailed::class, [RecordQueueJobHistory::class, 'onFailed']);
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('queue', classNamespace: 'App\Core\Queue\Livewire');
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'queue-manager');

        Blade::componentNamespace(
            'App\\Core\\Queue\\View\\Components',
            'queue-manager'
        );
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::middleware(['mobile', 'auth'])
            ->group(__DIR__.'/../Routes/mobile.php');

        Route::prefix('api')
            ->middleware(['api', 'auth:sanctum'])
            ->group(__DIR__.'/../Routes/api.php');
    }
}
