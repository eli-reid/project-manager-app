<?php

namespace App\Domains\Plans\Services;

use App\Core\Assets\Contracts\AssetAccessResolver;
use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetReference;
use App\Core\Identity\Models\User;
use App\Domains\Plans\Models\PlanSet;
use App\Domains\Plans\Policies\PlanSetPolicy;

class PlanAssetAccessResolver implements AssetAccessResolver
{
    public function __construct(private readonly PlanSetPolicy $policy) {}

    public function canView(User $user, Asset $asset, AssetReference $reference): bool
    {
        $planSet = $this->planSet($asset);

        return $planSet !== null && $this->policy->view($user, $planSet);
    }

    public function canDownload(User $user, Asset $asset, AssetReference $reference): bool
    {
        return $this->canView($user, $asset, $reference);
    }

    public function canShare(User $user, Asset $asset, AssetReference $reference): bool
    {
        return false;
    }

    private function planSet(Asset $asset): ?PlanSet
    {
        return PlanSet::query()->where('source_asset_id', $asset->id)->first();
    }
}
