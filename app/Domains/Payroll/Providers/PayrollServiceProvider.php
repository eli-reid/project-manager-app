<?php

namespace App\Domains\Payroll\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Identity\Services\UserRelationshipRegistry;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\UI\Navigation\Services\NavigationManager;
use App\Domains\Payroll\Contracts\ApprovedTimecardEntryProvider;
use App\Domains\Payroll\Contracts\PayrollTimecardReadGateway;
use App\Domains\Payroll\Models\Deduction;
use App\Domains\Payroll\Models\EmployeeDeduction;
use App\Domains\Payroll\Models\PayRate;
use App\Domains\Payroll\Models\PayrollEmployeeProfile;
use App\Domains\Payroll\Models\PayrollStatement;
use App\Domains\Payroll\Models\PayRun;
use App\Domains\Payroll\Notifications\PayrollNotificationDefinitions;
use App\Domains\Payroll\Observers\PayRateObserver;
use App\Domains\Payroll\Observers\PayrollAuditObserver;
use App\Domains\Payroll\Permissions\PayrollPermissions;
use App\Domains\Payroll\Policies\PayrollReportPolicy;
use App\Domains\Payroll\Reports\PayrollForecastingReportDefinitions;
use App\Domains\Payroll\Reports\PayrollReportDefinitions;
use App\Domains\Payroll\Tasks\PayrollDigestValidationTask;
use App\Domains\Reports\Services\ReportRegistry;
use App\Domains\Timecards\Services\EloquentApprovedTimecardEntryProvider;
use App\Domains\Timecards\Services\EloquentPayrollTimecardReadGateway;
use App\Providers\Concerns\RegistersNavigationItems;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PayrollServiceProvider extends ServiceProvider
{
    use RegistersNavigationItems;

    public function register(): void
    {
        $this->app->bind(ApprovedTimecardEntryProvider::class, EloquentApprovedTimecardEntryProvider::class);
        $this->app->bind(PayrollTimecardReadGateway::class, EloquentPayrollTimecardReadGateway::class);
    }

    public function boot(PermissionRegistryContract $permissionRegistry, NotificationRegistry $notificationRegistry, ReportRegistry $reportRegistry, TaskTypeRegistry $taskTypeRegistry, NavigationManager $navigationManager): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerAuthorization();
        $this->registerReports($reportRegistry);
        $this->registerNavigation($navigationManager);
        $this->registerSchedulerTasks($taskTypeRegistry);
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
        $this->registerUserRelations(app(UserRelationshipRegistry::class));
    }

    private function registerUserRelations(UserRelationshipRegistry $registry): void
    {
        $registry->register('payrollProfile', function ($user) {
            return $user->hasOne(PayrollEmployeeProfile::class);
        });

        $registry->register('payrollStatements', function ($user) {
            return $user->hasMany(PayrollStatement::class);
        });

        $registry->register('createdPayRuns', function ($user) {
            return $user->hasMany(PayRun::class, 'created_by');
        });

        $registry->register('approvedPayRuns', function ($user) {
            return $user->hasMany(PayRun::class, 'approved_by');
        });
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
        Gate::define('payroll-employees.view', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-employees.view'));
        Gate::define('payroll-employees.create', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-employees.create'));
        Gate::define('payroll-employees.update', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-employees.update'));
        Gate::define('payroll-employees.deactivate', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-employees.deactivate'));
        Gate::define('payroll-timecards.view', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-timecards.view'));
        Gate::define('payroll-runs.preview', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.preview'));
        Gate::define('payroll-runs.approve', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.approve'));
        Gate::define('payroll-runs.finalize', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.finalize'));
        Gate::define('payroll-runs.void', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.void'));
        Gate::define('payroll-runs.adjust-hours', fn ($user): bool => $user->isAdmin() || $user->hasPermission('payroll-runs.adjust-hours'));
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
        Livewire::addNamespace('payroll', classNamespace: 'App\Domains\Payroll\Livewire');
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
        $reportRegistry->registerDefinitions(PayrollForecastingReportDefinitions::all());
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(PayrollNotificationDefinitions::definitions());
    }

    private function registerNavigation(NavigationManager $navigationManager): void
    {
        $this->registerAdminNavigationItem($navigationManager, 'admin-payroll-timecard-review', 'Timecard Review', 'admin.payroll.timecards.review', 'receipt-percent', 70, [$this->gatePermission('payroll-timecards.view')]);
        $this->registerAdminNavigationItem($navigationManager, 'admin-payroll-weekly-employee-hours', 'Weekly Employee Hours', 'admin.payroll.reports.weekly-employee-hours', 'chart-bar-square', 71, [$this->gatePermission('payroll-runs.preview')]);
        $this->registerAdminNavigationItem($navigationManager, 'admin-payroll-weekly-hour-adjustments', 'Weekly Hour Adjustments', 'admin.payroll.reports.weekly-hour-adjustments', 'adjustments-horizontal', 72, [$this->gatePermission('payroll-runs.preview')]);
        $this->registerAdminNavigationItem($navigationManager, 'admin-payroll-rates', 'Rates', 'admin.payroll.rates.index', 'currency-dollar', 73, [$this->gatePermission('payroll-rates.view')]);
        $this->registerAdminNavigationItem($navigationManager, 'admin-payroll-rate-types', 'Rate Types', 'admin.payroll.rate-types.index', 'swatch', 74, [$this->gatePermission('payroll-rates.view')]);
        $this->registerAdminNavigationItem($navigationManager, 'admin-payroll-runs', 'Runs', 'admin.payroll.runs.index', 'banknotes', 75, [$this->gatePermission('payroll-runs.preview')]);
    }

    private function registerSchedulerTasks(TaskTypeRegistry $taskTypeRegistry): void
    {
        $taskTypeRegistry->register('payroll_audit_digest_validation', PayrollDigestValidationTask::class, [
            'name' => 'Payroll Audit Digest Validation',
            'description' => 'Validates payroll audit digest chain and emits alerts on integrity failures.',
            'task_config' => [
                'chain_key' => 'payroll',
            ],
        ]);
    }
}
