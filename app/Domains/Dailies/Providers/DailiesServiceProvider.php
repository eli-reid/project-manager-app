<?php

namespace App\Domains\Dailies\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use App\Domains\Dailies\Models\DailyReport;
use App\Domains\Dailies\Permissions\DailyPermissions;
use App\Domains\Dailies\Policies\DailyReportPolicy;
use App\Domains\Dailies\Support\DailiesProjectTab;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\Reports\Services\ReportRegistry;
use App\Providers\Concerns\RegistersMobileRedirectMappings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DailiesServiceProvider extends ServiceProvider
{
    use RegistersMobileRedirectMappings;

    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, ReportRegistry $reportRegistry, DashboardWidgetRegistry $widgetRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerMobileRoutePrefixMapping('dailies.', 'dailies.mobile.');

        $this->registerPermissions($permissionRegistry);
        $this->registerReports($reportRegistry);
        $this->registerProjectTabs($projectTabRegistry);
        $this->registerDashboardWidgets($widgetRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerDashboardWidgets(DashboardWidgetRegistry $widgetRegistry): void
    {
        $widgetRegistry->registerDefinitions([
            new WidgetDefinition(
                key: 'dailies.field-summary',
                component: 'dailies::dashboard.widget',
                section: 'operations',
                sort: 25,
                span: 'half',
                ability: 'viewAny',
                abilityModel: DailyReport::class,
                title: 'Daily Reports',
                description: 'Draft and submitted daily report activity.',
            ),
        ]);
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
        Livewire::addNamespace('dailies', classNamespace: 'App\Domains\Dailies\Livewire');
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

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            DailiesProjectTab::class,
        ]);
    }
}
