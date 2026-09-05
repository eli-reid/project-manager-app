<?php

namespace App\Domains\PaymentReceipts\Providers;

use App\Core\Auth\Permission\Contracts\PermissionRegistryContract;
use App\Domains\PaymentReceipts\Models\PaymentReceipt;
use App\Domains\PaymentReceipts\Permissions\PaymentReceiptPermissions;
use App\Domains\PaymentReceipts\Policies\PaymentReceiptPolicy;
use App\Domains\PaymentReceipts\Support\PaymentReceiptsProjectTab;
use App\Domains\Projects\Services\ProjectTabRegistry;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PaymentReceiptsServiceProvider extends ServiceProvider
{
    public function boot(PermissionRegistryContract $permissionRegistry, ProjectTabRegistry $projectTabRegistry): void
    {
        $this->registerPermissions($permissionRegistry);
        $this->registerProjectTabs($projectTabRegistry);
        $this->registerAuthorization();
        $this->registerInfrastructure();
        $this->registerUiComponents();
    }

    private function registerPermissions(PermissionRegistryContract $permissionRegistry): void
    {
        $permissionRegistry->registerPermissions(PaymentReceiptPermissions::all());
    }

    private function registerProjectTabs(ProjectTabRegistry $projectTabRegistry): void
    {
        $projectTabRegistry->registerDefinitions([
            PaymentReceiptsProjectTab::class,
        ]);
    }

    private function registerAuthorization(): void
    {
        Gate::policy(PaymentReceipt::class, PaymentReceiptPolicy::class);
    }

    private function registerInfrastructure(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/Views', 'payment-receipts');
    }

    private function registerUiComponents(): void
    {
        Livewire::addNamespace('payment-receipts', classNamespace: 'App\Domains\PaymentReceipts\Livewire');
    }
}
