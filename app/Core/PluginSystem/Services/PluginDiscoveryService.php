<?php

namespace App\Core\PluginSystem\Services;

use App\Core\PluginSystem\Models\InstalledPlugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginDiscoveryService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function discoverRegisteredPlugins(): Collection
    {
        $providersPath = base_path('bootstrap/providers.php');

        if (! File::exists($providersPath)) {
            return collect();
        }

        /** @var array<int, class-string> $providers */
        $providers = require $providersPath;

        $installedPlugins = InstalledPlugin::query()
            ->get()
            ->keyBy('provider_class');

        return collect($providers)
            ->filter(fn (string $provider): bool => Str::startsWith($provider, 'App\\PlugIns\\'))
            ->map(function (string $provider) use ($installedPlugins): array {
                $module = Str::of($provider)
                    ->after('App\\PlugIns\\')
                    ->before('\\Providers\\')
                    ->value();

                /** @var InstalledPlugin|null $installedRecord */
                $installedRecord = $installedPlugins->get($provider);

                return [
                    'slug' => Str::of($module)->snake('-')->value(),
                    'name' => Str::headline($module),
                    'provider_class' => $provider,
                    'source_type' => InstalledPlugin::SOURCE_BUNDLED,
                    'status' => $installedRecord?->status ?? InstalledPlugin::STATUS_INSTALLED,
                    'security_status' => $installedRecord?->security_status ?? InstalledPlugin::SECURITY_APPROVED,
                    'record_id' => $installedRecord?->id,
                ];
            })
            ->values();
    }
}
