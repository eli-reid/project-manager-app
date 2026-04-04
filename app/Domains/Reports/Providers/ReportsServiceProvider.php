<?php

namespace App\Domains\Reports\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Reports\Livewire\User\FinancialReports\Index as FinancialReportsIndex;
use App\Domains\Reports\Permissions\ReportPermissions;
use App\Domains\Reports\Policies\ReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ReportsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerAuthorizationGates();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
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
        }, ReportPermissions::all()));
    }

    private function registerAuthorizationGates(): void
    {
        Gate::define('reports.financial.view', fn ($user): bool => app(ReportPolicy::class)->viewFinancial($user));
        Gate::define('reports.financial.export', fn ($user): bool => app(ReportPolicy::class)->exportFinancial($user));
        Gate::define('reports.operational.view', fn ($user): bool => app(ReportPolicy::class)->viewOperational($user));
        Gate::define('reports.operational.export', fn ($user): bool => app(ReportPolicy::class)->exportOperational($user));
        Gate::define('reports.operational.create-template', fn ($user): bool => app(ReportPolicy::class)->createOperationalTemplate($user));
        Gate::define('reports.operational.schedule', fn ($user): bool => app(ReportPolicy::class)->scheduleOperational($user));
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'reports');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.reports.livewire.user.financial-reports', FinancialReportsIndex::class);
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
}
