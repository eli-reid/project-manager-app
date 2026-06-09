<?php

namespace App\Domains\Assets\Contracts;

use App\Domains\Assets\Models\Asset;
use App\Domains\Assets\Models\AssetShare;
use DateTimeInterface;

interface AssetSharingContract
{
    public function createShare(Asset $asset, array $opts = []): AssetShare;

    public function updateExpiration(AssetShare $share, ?DateTimeInterface $expiresAt): AssetShare;

    public function revokeShare(AssetShare $share): void;
}
