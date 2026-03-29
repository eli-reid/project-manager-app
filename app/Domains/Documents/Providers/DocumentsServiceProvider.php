<?php

namespace App\Domains\Documents\Providers;

use App\Core\User\Services\PermissionRegistry;
use App\Domains\Documents\Livewire\Admin\Documents\Index as AdminDocumentsIndex;
use App\Domains\Documents\Livewire\Admin\Projects\DocumentsTab;
use App\Domains\Documents\Livewire\User\Documents\GlobalIndex;
use App\Domains\Documents\Livewire\User\Documents\Index as UserDocumentsIndex;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Permissions\DocumentPermissions;
use App\Domains\Documents\Policies\DocumentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(PermissionRegistry $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);

        Gate::policy(Document::class, DocumentPolicy::class);

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'documents');

        Livewire::component('app.domains.documents.livewire.admin.documents.index', AdminDocumentsIndex::class);
        Livewire::component('app.domains.documents.livewire.user.documents.index', UserDocumentsIndex::class);
        Livewire::component('app.domains.documents.livewire.user.documents.global-index', GlobalIndex::class);
        Livewire::component('app.domains.documents.livewire.admin.projects.documents-tab', DocumentsTab::class);

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
        }, DocumentPermissions::all()));
    }
}
