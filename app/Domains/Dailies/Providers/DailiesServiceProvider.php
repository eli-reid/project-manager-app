<?php

namespace App\Domains\Dailies\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Dailies\Livewire\Admin\Dailies\Form as AdminForm;
use App\Domains\Dailies\Livewire\Admin\Dailies\Index as AdminIndex;
use App\Domains\Dailies\Livewire\Admin\Dailies\Show as AdminShow;
use App\Domains\Dailies\Livewire\User\Dailies\Form as UserForm;
use App\Domains\Dailies\Livewire\User\Dailies\Index as UserIndex;
use App\Domains\Dailies\Livewire\User\Dailies\Show as UserShow;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Dailies\Permissions\DailyPermissions;
use App\Domains\Dailies\Policies\DailyReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DailiesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(DailyReport::class, DailyReportPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'dailies');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.dailies.livewire.admin.dailies', AdminIndex::class);
        Livewire::component('app.domains.dailies.livewire.admin.dailies.form', AdminForm::class);
        Livewire::component('app.domains.dailies.livewire.admin.dailies.show', AdminShow::class);
        Livewire::component('app.domains.dailies.livewire.user.dailies', UserIndex::class);
        Livewire::component('app.domains.dailies.livewire.user.dailies.form', UserForm::class);
        Livewire::component('app.domains.dailies.livewire.user.dailies.show', UserShow::class);
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/mobile.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::prefix('api')
            ->name('api.')
            ->middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/api.php');
    }

    private function registerPermissions(PermissionRegistry $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
            ];
        }, DailyPermissions::all()));
    }
}
