<?php

namespace App\Core\PluginSystem\Livewire\Admin\Plugins;

use App\Core\PluginSystem\Models\InstalledPlugin;
use App\Core\PluginSystem\Services\PluginDiscoveryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Plugin System')]
class Index extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('viewAny', InstalledPlugin::class);
    }

    public function render(PluginDiscoveryService $pluginDiscoveryService)
    {
        return view('plugins::livewire.admin.plugins.index', [
            'registeredPlugins' => $pluginDiscoveryService->discoverRegisteredPlugins(),
            'installedPlugins' => InstalledPlugin::query()->latest('name')->get(),
        ]);
    }
}
