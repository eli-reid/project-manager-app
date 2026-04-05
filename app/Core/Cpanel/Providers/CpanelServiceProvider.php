<?php

namespace App\Core\Cpanel\Providers;

use App\Core\Cpanel\Commands\EnsureLaravelCronJobs;
use App\Core\Cpanel\Commands\SyncEmailAccounts;
use App\Core\Cpanel\Data\CpanelConfig;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Create as EmailAccountsCreate;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Index as EmailAccountsIndex;
use App\Core\Cpanel\Livewire\Admin\EmailAccounts\Show as EmailAccountsShow;
use App\Core\Cpanel\Livewire\Admin\EmailManagement\Dashboard as EmailManagementDashboard;
use App\Core\Cpanel\Livewire\Admin\EmailManagement\DomainForwarders as EmailManagementDomainForwarders;
use App\Core\Cpanel\Permissions\CpanelPermissions;
use App\Core\Cpanel\Services\CpanelMailboxManager;
use App\Core\Cpanel\Services\CpanelService;
use App\Core\Settings\Contracts\SettingsRegistryContract;
use App\Core\User\Contracts\PermissionRegistryContract;
use App\Core\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CpanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CpanelConfig::class);
        $this->app->singleton(CpanelService::class);
        $this->app->singleton(CpanelMailboxManager::class);
    }

    public function boot(SettingsRegistryContract $settingsRegistry): void
    {
        $this->registerSettings($settingsRegistry);
        $this->registerPermissions();
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
        $this->registerCommands();
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.core.cpanel.livewire.admin.email-management.dashboard', EmailManagementDashboard::class);
        Livewire::component('app.core.cpanel.livewire.admin.email-management.domain-forwarders', EmailManagementDomainForwarders::class);
        Livewire::component('app.core.cpanel.livewire.admin.email-accounts.index', EmailAccountsIndex::class);
        Livewire::component('app.core.cpanel.livewire.admin.email-accounts.create', EmailAccountsCreate::class);
        Livewire::component('app.core.cpanel.livewire.admin.email-accounts.show', EmailAccountsShow::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'cpanel');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->group(__DIR__.'/../Routes/web.php');

        Route::prefix('admin')
            ->name('admin.')
            ->middleware(['web', 'auth', 'can:manage-email-accounts'])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    private function registerCommands(): void
    {
        $this->commands([
            EnsureLaravelCronJobs::class,
            SyncEmailAccounts::class,
        ]);
    }

    private function registerSettings(SettingsRegistryContract $settingsRegistry): void
    {
        $settingsRegistry->registerConfigFile('cpanel', __DIR__.'/../config/settings.php');
    }

    private function registerPermissions(): void
    {
        /** @var PermissionRegistryContract $registry */
        $registry = $this->app->make(PermissionRegistryContract::class);

        $registry->registerPermissions(CpanelPermissions::all());
    }

    private function registerAuthorization(): void
    {
        Gate::define('manage-email-accounts', function (User $user): bool {
            return $user->isAdmin() || $user->hasPermission('cpanel.manage-email-accounts');
        });
    }
}
