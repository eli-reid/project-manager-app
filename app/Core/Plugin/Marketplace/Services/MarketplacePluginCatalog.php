<?php

namespace App\Core\PluginMarketplace\Services;

use App\Core\PluginSystem\Models\InstalledPlugin;
use Illuminate\Support\Collection;

class MarketplacePluginCatalog
{
    /**
     * @return Collection<int, InstalledPlugin>
     */
    public function all(): Collection
    {
        return InstalledPlugin::query()
            ->where('source_type', InstalledPlugin::SOURCE_MARKETPLACE)
            ->where('trust_level', InstalledPlugin::TRUST_REVIEWED_THIRD_PARTY)
            ->orderBy('name')
            ->get();
    }
}
