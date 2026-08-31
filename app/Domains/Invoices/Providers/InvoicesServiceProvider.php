<?php

namespace App\Domains\Invoices\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\Invoices\Console\Commands\PruneInvoicePdfImportsCommand;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Permissions\InvoicePermissions;
use App\Domains\Invoices\Policies\InvoicePolicy;
use App\Domains\Reports\Services\ReportRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class InvoicesServiceProvider extends ServiceProvider
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
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'invoices');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PruneInvoicePdfImportsCommand::class,
            ]);

            $this->app->booted(function (): void {
                Schedule::command(PruneInvoicePdfImportsCommand::class)
                    ->daily()
                    ->withoutOverlapping();
            });
        }
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('invoices', classNamespace: 'App\Domains\Invoices\Livewire');
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
        $permissionRegistry->registerPermissions(InvoicePermissions::all());
    }

    private function registerReports(ReportRegistry $reportRegistry): void
    {
        $reportRegistry->registerDefinitions([
            [
                'key' => 'financial.monthly-performance',
                'section' => 'financial',
                'title' => 'Monthly Financial Performance',
                'description' => 'Track month-over-month financial performance trends.',
                'route' => 'reports.financial.monthly-performance.index',
                'badge_label' => 'Available',
                'badge_color' => 'green',
                'sort' => 10,
            ],
        ]);
    }
}
