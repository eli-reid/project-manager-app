<?php

namespace App\Domains\Stock\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\Reports\Services\ReportRegistry;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use App\Domains\Stock\Permissions\StockOrderPermissions;
use App\Domains\Stock\Permissions\StockOrderTemplatePermissions;
use App\Domains\Stock\Policies\StockOrderPolicy;
use App\Domains\Stock\Policies\StockOrderTemplatePolicy;
use App\Domains\Stock\Support\StockProjectTab;
use App\Providers\Concerns\RegistersMobileRedirectMappings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class StockServiceProvider extends ServiceProvider
{
    use RegistersMobileRedirectMappings;

    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, ReportRegistry $reportRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerMobileRoutePrefixMapping('stock-orders.', 'stock-orders.mobile.');

        $this->registerPermissions($permissionRegistry);
        $this->registerReports($reportRegistry);
        $this->registerProjectTabs($projectTabRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(StockOrder::class, StockOrderPolicy::class);
        Gate::policy(StockOrderTemplate::class, StockOrderTemplatePolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'stock');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('stock', classNamespace: 'App\Domains\Stock\Livewire');
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
        $definitions = [
            ...StockOrderPermissions::all(),
            ...StockOrderTemplatePermissions::all(),
        ];

        $permissionRegistry->registerPermissions($definitions);
    }

    private function registerReports(ReportRegistry $reportRegistry): void
    {
        $reportRegistry->registerDefinitions([
            [
                'key' => 'financial.material-cost-analysis',
                'section' => 'financial',
                'title' => 'Material Cost Analysis',
                'description' => 'Review material and vendor cost distribution by project and period.',
                'route' => 'reports.financial.material-cost-analysis.index',
                'badge_label' => 'Available',
                'badge_color' => 'green',
                'sort' => 30,
            ],
        ]);
    }

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            StockProjectTab::class,
        ]);
    }
}
