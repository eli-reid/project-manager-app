<?php

use App\Core\PluginMarketplace\Services\MarketplacePluginCatalog;
use App\Core\PluginSystem\Models\InstalledPlugin;

it('lists reviewed marketplace plugins from the marketplace scaffold', function (): void {
    InstalledPlugin::factory()->create([
        'slug' => 'reviewed-marketplace-plugin',
        'name' => 'Reviewed Marketplace Plugin',
        'source_type' => InstalledPlugin::SOURCE_MARKETPLACE,
        'trust_level' => InstalledPlugin::TRUST_REVIEWED_THIRD_PARTY,
        'execution_mode' => InstalledPlugin::EXECUTION_IN_PROCESS_LIMITED,
    ]);

    InstalledPlugin::factory()->create([
        'slug' => 'bundled-system-plugin',
        'name' => 'Bundled System Plugin',
        'source_type' => InstalledPlugin::SOURCE_BUNDLED,
        'trust_level' => InstalledPlugin::TRUST_FIRST_PARTY,
        'execution_mode' => InstalledPlugin::EXECUTION_IN_PROCESS_FULL,
    ]);

    $plugins = app(MarketplacePluginCatalog::class)->all();

    expect($plugins->pluck('slug')->all())
        ->toBe(['reviewed-marketplace-plugin']);
});
