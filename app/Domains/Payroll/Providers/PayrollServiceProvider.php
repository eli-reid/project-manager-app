<?php

namespace App\Domains\Payroll\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Domains\Payroll\Livewire\Admin\PayRates\Form as AdminPayRateForm;
use App\Domains\Payroll\Livewire\Admin\PayRates\Index as AdminPayRateIndex;
use App\Domains\Payroll\Livewire\Admin\PayRateTypes\Index as AdminPayRateTypeIndex;
use App\Domains\Payroll\Livewire\Admin\PayRuns\Form as AdminPayRunForm;
use App\Domains\Payroll\Livewire\Admin\PayRuns\Index as AdminPayRunIndex;
use App\Domains\Payroll\Livewire\Admin\PayRuns\Show as AdminPayRunShow;
use App\Domains\Payroll\Livewire\Admin\Timecards\Review as AdminTimecardReview;
use App\Domains\Payroll\Livewire\User\PayrollHistory\Index as UserPayrollHistoryIndex;
use App\Domains\Payroll\Livewire\User\PayrollHistory\Show as UserPayrollHistoryShow;
use App\Domains\Payroll\Livewire\User\Reports\Audit\Index as PayrollAuditIndex;
use App\Domains\Payroll\Livewire\User\Reports\CertifiedPayroll\Index as CertifiedPayrollIndex;
use App\Domains\Payroll\Livewire\User\Reports\LaborCost\Index as PayrollLaborCostIndex;
use App\Domains\Payroll\Livewire\User\Reports\TaxFilings\Index as PayrollTaxFilingsIndex;
use App\Domains\Payroll\Livewire\User\Reports\UnionRemittance\Index as PayrollUnionRemittanceIndex;
use App\Domains\Payroll\Models\Deduction;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Notifications\PayrollNotificationDefinitions;
use App\Domains\Payroll\Observers\PayRateObserver;
use App\Domains\Payroll\Observers\PayrollAuditObserver;
use App\Domains\Payroll\Permissions\PayrollPermissions;
use App\Domains\Payroll\Policies\PayrollReportPolicy;
use App\Domains\Payroll\Reports\PayrollReportDefinitions;
use App\Domains\Payroll\Tasks\PayrollDigestValidationTask;
use App\Domains\Reports\Services\ReportRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PayrollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(SettingsRegistryContract $settingsRegistry, PermissionRegistryContract $permissionRegistry, NotificationRegistry $notificationRegistry, ReportRegistry $reportRegistry, ?TaskTypeRegistry $taskTypeRegistry = null): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerAuthorization();
        $this->registerReports($reportRegistry);
        $this->registerSchedulerTasks($taskTypeRegistry);
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
        PayRate::observe(PayRateObserver::class);
        PayRate::observe(PayrollAuditObserver::class);
        PayrollEmployeeProfile::observe(PayrollAuditObserver::class);
        PayrollStatement::observe(PayrollAuditObserver::class);
        Deduction::observe(PayrollAuditObserver::class);
        EmployeeDeduction::observe(PayrollAuditObserver::class);

        Gate::define('payroll-rates.view', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-rates.view'));
        Gate::define('payroll-rates.manage', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-rates.manage'));
        Gate::define('payroll-timecards.view', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-timecards.view'));
        Gate::define('payroll-runs.preview', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.preview'));
        Gate::define('payroll-runs.approve', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.approve'));
        Gate::define('payroll-runs.finalize', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.finalize'));
        Gate::define('payroll-runs.void', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.void'));
        Gate::define('payroll-stubs.view-own', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-stubs.view-own'));
        Gate::define('payroll-stubs.view-all', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-stubs.view-all'));

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
        Livewire::component('app.domains.payroll.livewire.admin.pay-rate-types', AdminPayRateTypeIndex::class);
        Livewire::component('app.domains.payroll.livewire.admin.pay-rates', AdminPayRateIndex::class);
        Livewire::component('app.domains.payroll.livewire.admin.pay-rates.form', AdminPayRateForm::class);
        Livewire::component('app.domains.payroll.livewire.admin.timecards.review', AdminTimecardReview::class);
        Livewire::component('app.domains.payroll.livewire.admin.pay-runs', AdminPayRunIndex::class);
        Livewire::component('app.domains.payroll.livewire.admin.pay-runs.form', AdminPayRunForm::class);
        Livewire::component('app.domains.payroll.livewire.admin.pay-runs.show', AdminPayRunShow::class);

        Livewire::component('app.domains.payroll.livewire.user.payroll-history', UserPayrollHistoryIndex::class);
        Livewire::component('app.domains.payroll.livewire.user.payroll-history.show', UserPayrollHistoryShow::class);
        Livewire::component('app.domains.payroll.livewire.user.reports.certified-payroll', CertifiedPayrollIndex::class);
        Livewire::component('app.domains.payroll.livewire.user.reports.tax-filings', PayrollTaxFilingsIndex::class);
        Livewire::component('app.domains.payroll.livewire.user.reports.labor-cost', PayrollLaborCostIndex::class);
        Livewire::component('app.domains.payroll.livewire.user.reports.union-remittance', PayrollUnionRemittanceIndex::class);
        Livewire::component('app.domains.payroll.livewire.user.reports.audit', PayrollAuditIndex::class);
    }

    private function registerRoutes(): void
    {
        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/web.php');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(PayrollPermissions::all());
    }

    private function registerReports(ReportRegistry $reportRegistry): void
    {
        $reportRegistry->registerDefinitions(PayrollReportDefinitions::all());
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(PayrollNotificationDefinitions::definitions());
    }

    private function registerSchedulerTasks(?TaskTypeRegistry $taskTypeRegistry): void
    {
        if ($taskTypeRegistry === null && ! $this->app->bound(TaskTypeRegistry::class)) {
            Log::warning('TaskTypeRegistry not bound in container. Payroll digest validation task not registered.');

            return;
        }

        $registry = $taskTypeRegistry ?? $this->app->make(TaskTypeRegistry::class);

        $registry->register('payroll_audit_digest_validation', PayrollDigestValidationTask::class, [
            'name' => 'Payroll Audit Digest Validation',
            'description' => 'Validates payroll audit digest chain and emits alerts on integrity failures.',
            'task_config' => [
                'chain_key' => 'payroll',
            ],
        ]);
    }
}
