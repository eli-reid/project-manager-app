<?php

namespace App\Domains\Timecards\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\Reports\Services\ReportRegistry;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;
use App\Domains\Timecards\Observers\TimecardEntryObserver;
use App\Domains\Timecards\Observers\TimecardObserver;
use App\Domains\Timecards\Permissions\TimecardPermissions;
use App\Domains\Timecards\Policies\TimecardPolicy;
use App\Domains\Timecards\Reports\TimecardReportDefinitions;
use App\Domains\Timecards\Support\TimecardsProjectTab;
use App\Domains\Timecards\Tasks\TimecardReminderTask;
use App\Providers\Concerns\RegistersMobileRedirectMappings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TimecardsServiceProvider extends ServiceProvider
{
    use RegistersMobileRedirectMappings;

    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, NotificationRegistry $notificationRegistry, ReportRegistry $reportRegistry, TaskTypeRegistry $taskTypeRegistry, DashboardWidgetRegistry $widgetRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerMobileRoutePrefixMapping('timecards.', 'timecards.mobile.');

        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerReports($reportRegistry);
        $this->registerProjectTabs($projectTabRegistry);
        $this->registerSchedulerTasks($taskTypeRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerDashboardWidgets($widgetRegistry);
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Timecard::class, TimecardPolicy::class);
        TimecardEntry::observe(TimecardEntryObserver::class);
        Timecard::observe(TimecardObserver::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'timecards');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('timecards', classNamespace: 'App\Domains\Timecards\Livewire');
    }

    private function registerDashboardWidgets(DashboardWidgetRegistry $widgetRegistry): void
    {
        $widgetRegistry->registerDefinitions([
            new WidgetDefinition(
                key: 'timecards.my-week',
                component: 'timecards::dashboard.widget',
                section: 'personal',
                sort: 20,
                span: 'half',
                ability: 'viewAny',
                abilityModel: Timecard::class,
                title: 'My Timecards',
                description: 'Recent timecard activity.',
            ),
        ]);
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
        $permissionRegistry->registerPermissions(TimecardPermissions::all());
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(TimecardNotificationDefinitions::definitions());
    }

    private function registerReports(ReportRegistry $reportRegistry): void
    {
        $reportRegistry->registerDefinitions(TimecardReportDefinitions::all());
    }

    private function registerSchedulerTasks(TaskTypeRegistry $taskTypeRegistry): void
    {
        if (! $this->app->bound(TaskTypeRegistry::class)) {
            Log::warning('TaskTypeRegistry not bound in container. Timecard reminder task not registered.');

            return;
        }

        $taskTypeRegistry->register('timecard_reminders', TimecardReminderTask::class, [
            'name' => 'Timecard Reminders',
            'description' => 'Sends reminders to users with pending timecards.',
            'task_config' => [
                'days_after_week_end' => 0,
                'statuses' => [
                    'draft',
                    'rejected',
                ],
            ],
        ]);
    }

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            TimecardsProjectTab::class,
        ]);
    }
}
