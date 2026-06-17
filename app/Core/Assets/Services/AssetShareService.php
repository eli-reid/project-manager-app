<?php

namespace App\Core\Assets\Services;

use App\Core\Assets\Contracts\AssetSharingContract;
use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetShare;
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
