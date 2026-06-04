<?php

namespace App\Domains\Submittals\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Identity\Models\User;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Domains\Submittals\Models\Submittal;
use App\Domains\Submittals\Permissions\SubmittalPermissions;
use App\Domains\Submittals\Policies\SubmittalPolicy;
use App\Providers\Concerns\RegistersMobileRedirectMappings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SubmittalsServiceProvider extends ServiceProvider
{
    use RegistersMobileRedirectMappings;

    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerMobileRoutePrefixMapping('submittals.', 'submittals.mobile.');

        $this->registerPermissions($permissionRegistry);
        $this->registerProjectTabs($projectTabRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Submittal::class, SubmittalPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'submittals');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('submittals', classNamespace: 'App\\Domains\\Submittals\\Livewire');
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
        $permissionRegistry->registerPermissions(SubmittalPermissions::all());
    }

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            [
                'key' => 'submittals',
                'label' => 'Submittals',
                'sort' => 60,
                'mode_param' => 'submittalMode',
                'detail_query_param' => 'submittalId',
                'badge_count' => static fn (User $user, Project $project): ?int => $user->can('viewAny', Submittal::class)
                    ? Submittal::query()->where('project_id', $project->id)->count()
                    : 0,
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Submittal::class)
                    || $user->can('create', Submittal::class),
            ],
        ]);
    }
}
