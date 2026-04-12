<?php

namespace App\Domains\Timecards\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Domains\Reports\Services\ReportRegistry;
use App\Domains\Timecards\Livewire\Admin\Timecards\Form as AdminForm;
use App\Domains\Timecards\Livewire\Admin\Timecards\Index;
use App\Domains\Timecards\Livewire\Admin\Timecards\Show as AdminShow;
use App\Domains\Timecards\Livewire\User\Timecards\Form as UserForm;
use App\Domains\Timecards\Livewire\User\Timecards\Index as UserIndex;
use App\Domains\Timecards\Livewire\User\Timecards\Show as UserShow;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardEntry;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;
use App\Domains\Timecards\Observers\TimecardEntryObserver;
use App\Domains\Timecards\Permissions\TimecardPermissions;
use App\Domains\Timecards\Policies\TimecardPolicy;
use App\Domains\Timecards\Reports\TimecardReportDefinitions;
use App\Domains\Timecards\Tasks\TimecardReminderTask;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TimecardsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, NotificationRegistry $notificationRegistry, ReportRegistry $reportRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerReports($reportRegistry);
        $this->registerSchedulerTasks();
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Timecard::class, TimecardPolicy::class);
        TimecardEntry::observe(TimecardEntryObserver::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'timecards');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.timecards.livewire.admin.timecards', Index::class);
        Livewire::component('app.domains.timecards.livewire.admin.timecards.form', AdminForm::class);
        Livewire::component('app.domains.timecards.livewire.admin.timecards.show', AdminShow::class);
        Livewire::component('app.domains.timecards.livewire.user.timecards', UserIndex::class);
        Livewire::component('app.domains.timecards.livewire.user.timecards.form', UserForm::class);
        Livewire::component('app.domains.timecards.livewire.user.timecards.show', UserShow::class);
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

    private function registerSchedulerTasks(): void
    {
        if (! $this->app->bound(TaskTypeRegistry::class)) {
            Log::warning('TaskTypeRegistry not bound in container. Timecard reminder task not registered.');

            return;
        }

        $this->app->make(TaskTypeRegistry::class)->register('timecard_reminders', TimecardReminderTask::class, [
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
        Log::info('Registered timecard reminder task with TaskTypeRegistry.');

    }
}
