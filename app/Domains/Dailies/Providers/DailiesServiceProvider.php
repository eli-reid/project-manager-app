<?php

namespace App\Domains\Dailies\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\Dailies\Livewire\Admin\Dailies\Form as AdminForm;
use App\Domains\Dailies\Livewire\Admin\Dailies\Index as AdminIndex;
use App\Domains\Dailies\Livewire\Admin\Dailies\Show as AdminShow;
use App\Domains\Dailies\Livewire\User\Dailies\Form as UserForm;
use App\Domains\Dailies\Livewire\User\Dailies\Index as UserIndex;
use App\Domains\Dailies\Livewire\User\Dailies\Show as UserShow;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Dailies\Permissions\DailyPermissions;
use App\Domains\Dailies\Policies\DailyReportPolicy;
use App\Domains\Reports\Services\ReportRegistry;
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

    public function boot(PermissionRegistryContract $permissionRegistry, ReportRegistry $reportRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerReports($reportRegistry);
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

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(DailyPermissions::all());
    }

    private function registerReports(ReportRegistry $reportRegistry): void
    {
        $reportRegistry->registerDefinitions([
            [
                'key' => 'operational.daily-reports',
                'section' => 'operational',
                'title' => 'Daily Reports Workspace',
                'description' => 'Monitor daily field activity and submitted reports.',
                'route' => 'dailies.index',
                'badge_label' => 'Operational',
                'badge_color' => 'sky',
                'sort' => 10,
            ],
        ]);
    }
}
