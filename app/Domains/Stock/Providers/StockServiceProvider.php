<?php

namespace App\Domains\Stock\Providers;

use App\Core\User\Contracts\PermissionRegistryContract;
use App\Domains\Stock\Livewire\Admin\StockOrders\Index as AdminStockOrdersIndex;
use App\Domains\Stock\Livewire\Admin\StockOrders\Show as AdminStockOrdersShow;
use App\Domains\Stock\Livewire\Admin\Templates\Form as AdminTemplatesForm;
use App\Domains\Stock\Livewire\Admin\Templates\Index as AdminTemplatesIndex;
use App\Domains\Stock\Livewire\User\StockOrders\Form;
use App\Domains\Stock\Livewire\User\StockOrders\Index;
use App\Domains\Stock\Livewire\User\StockOrders\Show;
use App\Domains\Stock\Livewire\User\Templates\Browse;
use App\Domains\Stock\Livewire\User\Templates\FromTemplate;
use App\Domains\Stock\Models\StockOrder;
use App\Domains\Stock\Models\StockOrderTemplate;
use App\Domains\Stock\Permissions\StockOrderPermissions;
use App\Domains\Stock\Permissions\StockOrderTemplatePermissions;
use App\Domains\Stock\Policies\StockOrderPolicy;
use App\Domains\Stock\Policies\StockOrderTemplatePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class StockServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(PermissionRegistryContract $permissionRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUIComponents();
        $this->registerRoutes();
    }

    private function registerAuthorization(): void
    {
        Gate::policy(StockOrder::class, StockOrderPolicy::class);
        Gate::policy(StockOrderTemplate::class, StockOrderTemplatePolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'stock');
    }

    private function registerUIComponents(): void
    {
        Livewire::component('app.domains.stock.livewire.user.stock-orders.index', Index::class);
        Livewire::component('app.domains.stock.livewire.user.stock-orders.form', Form::class);
        Livewire::component('app.domains.stock.livewire.user.stock-orders.show', Show::class);
        Livewire::component('app.domains.stock.livewire.user.templates.browse', Browse::class);
        Livewire::component('app.domains.stock.livewire.user.templates.from-template', FromTemplate::class);

        Livewire::component('app.domains.stock.livewire.admin.stock-orders.index', AdminStockOrdersIndex::class);
        Livewire::component('app.domains.stock.livewire.admin.stock-orders.show', AdminStockOrdersShow::class);
        Livewire::component('app.domains.stock.livewire.admin.templates.index', AdminTemplatesIndex::class);
        Livewire::component('app.domains.stock.livewire.admin.templates.form', AdminTemplatesForm::class);
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
            ...StockOrderPermissions::all(),
            ...StockOrderTemplatePermissions::all(),
        ];

        $permissionRegistry->registerPermissions($definitions);
    }
}
