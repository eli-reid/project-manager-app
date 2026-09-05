<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services;

use App\Core\Assets\Contracts\AssetAccessResolver;
use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetReference;
use App\Core\Identity\Models\User;
use App\Domains\Documents\Models\Document;
use App\Domains\Documents\Policies\DocumentPolicy;

class DocumentAssetAccessResolver implements AssetAccessResolver
{
    public function __construct(private readonly DocumentPolicy $policy) {}

    public function canView(User $user, Asset $asset, AssetReference $reference): bool
    {
        $document = Document::query()->where('asset_id', $asset->id)->first();

        if ($document === null) {
            return false;
        }

        return $this->policy->view($user, $document);
    }

    public function canDownload(User $user, Asset $asset, AssetReference $reference): bool
    {
        $document = Document::query()->where('asset_id', $asset->id)->first();

        if ($document === null) {
            return false;
        }

        return $this->policy->view($user, $document);
    }

    public function canShare(User $user, Asset $asset, AssetReference $reference): bool
    {
        $document = Document::query()->where('asset_id', $asset->id)->first();

        if ($document === null) {
            return false;
        }

        return $this->policy->share($user, $document);
    }
}
