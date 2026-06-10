<?php

namespace App\Domains\Documents\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Core\Dashboard\Data\WidgetDefinition;
use App\Core\Dashboard\Services\DashboardWidgetRegistry;
use App\Core\Identity\Models\User;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\Settings\Facades\Settings;
use App\Domains\Documents\Contracts\DocumentOrchestratorContract;
use App\Domains\Documents\Contracts\DocumentSharingContract;
use App\Domains\Documents\Contracts\ProjectDocumentLibraryContract;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Permissions\DocumentPermissions;
use App\Domains\Documents\Policies\DocumentPolicy;
use App\Domains\Documents\Services\DocumentService;
use App\Domains\Documents\Services\DocumentShareService;
use App\Domains\Documents\Services\ProjectDocumentLibrary;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Services\ProjectTabRegistry;
use App\Providers\Concerns\RegistersMobileRedirectMappings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DocumentsServiceProvider extends ServiceProvider
{
    use RegistersMobileRedirectMappings;

    public function register(): void
    {
        $this->app->singleton(DocumentOrchestratorContract::class, DocumentService::class);
        $this->app->singleton(DocumentSharingContract::class, DocumentShareService::class);
        $this->app->singleton(ProjectDocumentLibraryContract::class, ProjectDocumentLibrary::class);
        $this->commands([\App\Domains\Documents\Console\MigrateDocumentsToAssets::class]);
    }

    public function boot(PermissionRegistryContract $permissionRegistry, SettingsRegistryContract $settingsRegistry, DashboardWidgetRegistry $widgetRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerMobileExactRouteMapping('documents.index', 'documents.mobile.global');
        $this->registerMobileExactRouteMapping('documents.global', 'documents.mobile.global');

        $this->registerSettings($settingsRegistry);
        $this->configureLivewireTemporaryUploadRules();
        $this->registerPermissions($permissionRegistry);
        $this->registerProjectTabs($projectTabRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerDashboardWidgets($widgetRegistry);
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(Document::class, DocumentPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'documents');
    }

    private function registerUIComponents(): void
    {
        Livewire::addNamespace('documents', classNamespace: 'App\Domains\Documents\Livewire');
    }

    private function registerDashboardWidgets(DashboardWidgetRegistry $widgetRegistry): void
    {
        $widgetRegistry->registerDefinitions([
            new WidgetDefinition(
                key: 'documents.project-documents',
                component: 'documents::dashboard.widget',
                section: 'personal',
                sort: 25,
                span: 'half',
                ability: 'viewAny',
                abilityModel: Document::class,
                title: 'Project Documents',
                description: 'Project documents shared by your team.',
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

        Route::middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/sharing.php');

        Route::middleware(['web'])
            ->group(function (): void {
                require __DIR__.'/../Routes/public-sharing.php';
            });

        Route::prefix('api')
            ->name('api.')
            ->middleware(['web', 'auth', 'verified'])
            ->group(__DIR__.'/../Routes/api.php');
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(DocumentPermissions::all());
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('documents', __DIR__.'/../config/settings.php');
    }

    private function configureLivewireTemporaryUploadRules(): void
    {
        try {
            $maxKilobytes = max(1, Settings::get('documents.max_file_size', 10240)->toInt());
        } catch (\Throwable) {
            $maxKilobytes = 10240;
        }

        config()->set('livewire.temporary_file_upload.rules', [
            'required',
            'file',
            'max:'.$maxKilobytes,
        ]);
    }

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            [
                'key' => 'documents',
                'label' => 'Library',
                'sort' => 90,
                'badge_count' => static fn (User $user, Project $project): ?int => app(ProjectDocumentLibraryContract::class)
                    ->countProjectAccessible((string) $project->id),
                'is_visible' => static fn (User $user, Project $project): bool => $user->can('viewAny', Document::class),
            ],
        ]);
    }
}
