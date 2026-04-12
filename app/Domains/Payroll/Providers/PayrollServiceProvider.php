<?php

namespace App\Domains\Payroll\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Domains\Payroll\Livewire\User\Reports\CertifiedPayroll\Index as CertifiedPayrollIndex;
use App\Domains\Payroll\Livewire\User\Reports\LaborCost\Index as PayrollLaborCostIndex;
use App\Domains\Payroll\Livewire\User\Reports\TaxFilings\Index as PayrollTaxFilingsIndex;
use App\Domains\Payroll\Livewire\User\Reports\UnionRemittance\Index as PayrollUnionRemittanceIndex;
use App\Domains\Payroll\Permissions\PayrollPermissions;
use App\Domains\Payroll\Policies\PayrollReportPolicy;
use App\Domains\Reports\Services\ReportRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PayrollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(SettingsRegistryContract $settingsRegistry, PermissionRegistryContract $permissionRegistry, ReportRegistry $reportRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions($permissionRegistry);
        $this->registerAuthorization();
        $this->registerReports($reportRegistry);
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('payroll', __DIR__.'/../config/settings.php');
    }

    private function registerAuthorization(): void
    {
        Gate::define('payroll.view', fn ($user): bool => app(PayrollReportPolicy::class)->viewOwn($user));
        Gate::define('reports.payroll.view', fn ($user): bool => app(PayrollReportPolicy::class)->viewReports($user));
        Gate::define('reports.payroll.export', fn ($user): bool => app(PayrollReportPolicy::class)->exportReports($user));
        Gate::define('reports.payroll.generate', fn ($user): bool => app(PayrollReportPolicy::class)->generateReports($user));
        Gate::define('reports.payroll.certify', fn ($user): bool => app(PayrollReportPolicy::class)->certifyReports($user));
        Gate::define('reports.payroll.remit', fn ($user): bool => app(PayrollReportPolicy::class)->remitReports($user));
        Gate::define('reports.payroll.manage', fn ($user): bool => app(PayrollReportPolicy::class)->manageReports($user));
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'payroll');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.payroll.livewire.user.reports.certified-payroll', CertifiedPayrollIndex::class);
        Livewire::component('app.domains.payroll.livewire.user.reports.tax-filings', PayrollTaxFilingsIndex::class);
        Livewire::component('app.domains.payroll.livewire.user.reports.labor-cost', PayrollLaborCostIndex::class);
        Livewire::component('app.domains.payroll.livewire.user.reports.union-remittance', PayrollUnionRemittanceIndex::class);
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(PayrollPermissions::all());
    }

    private function registerReports(ReportRegistry $reportRegistry): void
    {
        $reportRegistry->registerDefinitions([
            [
                'key' => 'financial.payroll-certified-wh347',
                'section' => 'financial',
                'title' => 'Certified Payroll (WH-347)',
                'description' => 'Generate certified payroll by project and week.',
                'route' => 'reports.payroll.certified.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 60,
            ],
            [
                'key' => 'financial.payroll-tax-filings',
                'section' => 'financial',
                'title' => 'Payroll Tax Filings (941 and W-2)',
                'description' => 'Generate quarterly and annual payroll tax filing datasets.',
                'route' => 'reports.payroll.tax-filings.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 70,
            ],
            [
                'key' => 'financial.payroll-labor-cost',
                'section' => 'financial',
                'title' => 'Payroll Labor Cost by Project and Cost Code',
                'description' => 'Analyze payroll labor cost by project, cost code, and employee.',
                'route' => 'reports.payroll.labor-cost.index',
                'badge_label' => 'Financial',
                'badge_color' => 'green',
                'sort' => 80,
            ],
            [
                'key' => 'financial.payroll-union-remittance',
                'section' => 'financial',
                'title' => 'Union Remittance',
                'description' => 'Generate union remittance reports and export-ready files.',
                'route' => 'reports.payroll.union-remittance.index',
                'badge_label' => 'Compliance',
                'badge_color' => 'amber',
                'sort' => 90,
            ],
        ]);
    }
}
