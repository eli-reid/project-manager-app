<?php

namespace App\Domains\Tasks\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Domains\Tasks\Livewire\Admin\Projects\TaskHierarchyWidget;
use App\Domains\Tasks\Livewire\Admin\TaskCategories\Form as TaskCategoryForm;
use App\Domains\Tasks\Livewire\Admin\TaskCategories\Index as TaskCategoryIndex;
use App\Domains\Tasks\Livewire\Admin\Tasks\Form;
use App\Domains\Tasks\Livewire\Admin\Tasks\Index;
use App\Domains\Tasks\Livewire\Admin\TaskTemplates\Form as TaskTemplateForm;
use App\Domains\Tasks\Livewire\Admin\TaskTemplates\Index as TaskTemplateIndex;
use App\Domains\Tasks\Livewire\User\Tasks\Index as UserTaskIndex;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Models\TaskCategory;
use App\Domains\Tasks\Models\TaskTemplate;
use App\Domains\Tasks\Notifications\TaskNotificationDefinitions;
use App\Domains\Tasks\Observers\TaskCategoryObserver;
use App\Domains\Tasks\Permissions\TaskCategoryPermissions;
use App\Domains\Tasks\Permissions\TaskPermissions;
use App\Domains\Tasks\Permissions\TaskTemplatePermissions;
use App\Domains\Tasks\Policies\TaskCategoryPolicy;
use App\Domains\Tasks\Policies\TaskPolicy;
use App\Domains\Tasks\Policies\TaskTemplatePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class TasksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, NotificationRegistry $notificationRegistry, SettingsRegistryContract $settingsRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskCategory::class, TaskCategoryPolicy::class);
        Gate::policy(TaskTemplate::class, TaskTemplatePolicy::class);
        TaskCategory::observe(TaskCategoryObserver::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'tasks');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.tasks.livewire.admin.tasks', Index::class);
        Livewire::component('app.domains.tasks.livewire.admin.tasks.form', Form::class);
        Livewire::component('app.domains.tasks.livewire.admin.task-categories', TaskCategoryIndex::class);
        Livewire::component('app.domains.tasks.livewire.admin.task-categories.form', TaskCategoryForm::class);
        Livewire::component('app.domains.tasks.livewire.admin.task-templates', TaskTemplateIndex::class);
        Livewire::component('app.domains.tasks.livewire.admin.task-templates.form', TaskTemplateForm::class);
        Livewire::component('app.domains.tasks.livewire.admin.projects.task-hierarchy-widget', TaskHierarchyWidget::class);
        Livewire::component('app.domains.tasks.livewire.user.tasks', UserTaskIndex::class);
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
            ...TaskPermissions::all(),
            ...TaskCategoryPermissions::all(),
            ...TaskTemplatePermissions::all(),
        ];

        $permissionRegistry->registerPermissions($definitions);
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(TaskNotificationDefinitions::definitions());
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('tasks', __DIR__.'/../config/settings.php');
    }
}
