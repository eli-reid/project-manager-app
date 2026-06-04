<?php

namespace App\Domains\Invoices\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Identity\Models\User;
use App\Domains\Invoices\Models\Invoice;
use App\Domains\Invoices\Permissions\InvoicePermissions;
use App\Domains\Invoices\Policies\InvoicePolicy;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\Reports\Services\ReportRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class InvoicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, ReportRegistry $reportRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
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
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'invoices');
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

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            [
                'key' => 'invoices',
                'label' => 'Invoices',
                'sort' => 40,
                'mode_param' => 'invoiceMode',
                'detail_query_param' => 'invoiceId',
                'badge_count' => static fn (User $user, Project $project): ?int => Invoice::query()
                    ->where('project_id', $project->id)
                    ->count(),
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Invoice::class),
            ],
        ]);
    }
}
