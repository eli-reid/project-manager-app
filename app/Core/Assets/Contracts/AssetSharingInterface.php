<?php

declare(strict_types=1);

namespace App\Core\Assets\Contracts;

use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetShare;
use DateTimeInterface;

interface AssetSharingInterface
{
    public function createShare(Asset $asset, array $opts = []): AssetShare;

    public function updateExpiration(AssetShare $share, ?DateTimeInterface $expiresAt): AssetShare;

    public function revokeShare(AssetShare $share): void;
}
