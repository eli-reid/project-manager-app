<?php

namespace App\Domains\Timecards\Providers;

use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Scheduler\Services\TaskTypeRegistry;
use App\Core\User\Services\PermissionRegistry;
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
use App\Domains\Timecards\Tasks\TimecardReminderTask;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TimecardsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistry $permissionRegistry, NotificationRegistry $notificationRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerSchedulerTasks();

        Gate::policy(Timecard::class, TimecardPolicy::class);
        TimecardEntry::observe(TimecardEntryObserver::class);

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'timecards');

        Livewire::component('app.domains.timecards.livewire.admin.timecards', Index::class);
        Livewire::component('app.domains.timecards.livewire.admin.timecards.form', AdminForm::class);
        Livewire::component('app.domains.timecards.livewire.admin.timecards.show', AdminShow::class);
        Livewire::component('app.domains.timecards.livewire.user.timecards', UserIndex::class);
        Livewire::component('app.domains.timecards.livewire.user.timecards.form', UserForm::class);
        Livewire::component('app.domains.timecards.livewire.user.timecards.show', UserShow::class);

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
        }, TimecardPermissions::all()));
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(TimecardNotificationDefinitions::definitions());
    }

    private function registerSchedulerTasks(): void
    {
        if (! $this->app->bound(TaskTypeRegistry::class)) {
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
    }
}
