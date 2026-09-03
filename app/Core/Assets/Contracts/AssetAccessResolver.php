<?php

declare(strict_types=1);

namespace App\Core\Assets\Contracts;

use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetReference;
use App\Core\Identity\Models\User;

/**
 * Implemented by every domain that references assets.
 *
 * Assets owns the delivery mechanism; the owning domain owns the policy.
 * Implementations should delegate to the domain's existing policy rather than
 * re-implementing authorization rules.
 */
interface AssetAccessResolver
{
    public function canView(User $user, Asset $asset, AssetReference $reference): bool;

    public function canDownload(User $user, Asset $asset, AssetReference $reference): bool;

    public function canShare(User $user, Asset $asset, AssetReference $reference): bool;
}
