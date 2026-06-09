<?php

namespace App\Domains\Assets\Services;

use App\Domains\Assets\Contracts\AssetSharingContract;
use App\Domains\Assets\Models\Asset;
use App\Domains\Assets\Models\AssetShare;
use DateTimeInterface;

class AssetShareService implements AssetSharingContract
{
    public function createShare(Asset $asset, array $opts = []): AssetShare
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function updateExpiration(AssetShare $share, ?DateTimeInterface $expiresAt): AssetShare
    {
        throw new \BadMethodCallException('Not implemented.');
    }

    public function revokeShare(AssetShare $share): void
    {
        throw new \BadMethodCallException('Not implemented.');
    }
}
