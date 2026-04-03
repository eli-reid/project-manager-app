<?php

namespace App\Domains\Tasks\Providers;

use App\Core\Notification\Services\NotificationRegistry;
use App\Core\User\Services\PermissionRegistry;
use App\Domains\Tasks\Livewire\Admin\Projects\TaskHierarchyWidget;
use App\Domains\Tasks\Livewire\Admin\Tasks\Form;
use App\Domains\Tasks\Livewire\Admin\Tasks\Index;
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
    public function register(): void {}

    public function boot(PermissionRegistry $permissionRegistry, NotificationRegistry $notificationRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerNotifications($notificationRegistry);

        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskCategory::class, TaskCategoryPolicy::class);
        Gate::policy(TaskTemplate::class, TaskTemplatePolicy::class);
        TaskCategory::observe(TaskCategoryObserver::class);

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'tasks');

        Livewire::component('app.domains.tasks.livewire.admin.tasks', Index::class);
        Livewire::component('app.domains.tasks.livewire.admin.tasks.form', Form::class);
        Livewire::component('app.domains.tasks.livewire.admin.task-categories', \App\Domains\Tasks\Livewire\Admin\TaskCategories\Index::class);
        Livewire::component('app.domains.tasks.livewire.admin.task-categories.form', \App\Domains\Tasks\Livewire\Admin\TaskCategories\Form::class);
        Livewire::component('app.domains.tasks.livewire.admin.task-templates', \App\Domains\Tasks\Livewire\Admin\TaskTemplates\Index::class);
        Livewire::component('app.domains.tasks.livewire.admin.task-templates.form', \App\Domains\Tasks\Livewire\Admin\TaskTemplates\Form::class);
        Livewire::component('app.domains.tasks.livewire.admin.projects.task-hierarchy-widget', TaskHierarchyWidget::class);

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    private function registerPermissions(PermissionRegistry $permissionRegistry): void
    {
        $definitions = [
            ...TaskPermissions::all(),
            ...TaskCategoryPermissions::all(),
            ...TaskTemplatePermissions::all(),
        ];

        $permissionRegistry->registerPermissions(array_map(function (array $definition): array {
            $resource = (string) $definition['resource'];
            $action = (string) $definition['action'];

            return [
                'resource' => $resource,
                'action' => $action,
                'label' => $definition['label'] ?? str($resource.' '.$action)->replace(['_', '-'], ' ')->headline()->value(),
                'description' => $definition['description'] ?? '',
            ];
        }, $definitions));
    }

    private function registerNotifications(NotificationRegistry $notificationRegistry): void
    {
        $notificationRegistry->registerDefinitions(TaskNotificationDefinitions::definitions());
    }
}
