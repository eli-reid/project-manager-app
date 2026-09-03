<?php

declare(strict_types=1);

namespace App\Core\Assets\Services;

use App\Core\Assets\Models\Asset;
use App\Core\Assets\Models\AssetReference;
use App\Core\Identity\Models\User;

/**
 * Decides whether a user may act on an asset.
 *
 * An asset has no intrinsic owner, only references. A user may act on an asset
 * when any resolver for any of its references grants the ability. Access is
 * denied by default when no reference resolves to a registered resolver, which
 * is what makes content-hash deduplication safe across domains.
 */
class AssetGatekeeper
{
    public function __construct(
        private readonly AssetReferencerRegistry $registry,
    ) {}

    public function canView(User $user, Asset $asset): bool
    {
        return $this->allows($user, $asset, 'canView');
    }

    public function canDownload(User $user, Asset $asset): bool
    {
        return $this->allows($user, $asset, 'canDownload');
    }

    public function canShare(User $user, Asset $asset): bool
    {
        return $this->allows($user, $asset, 'canShare');
    }

    private function allows(User $user, Asset $asset, string $ability): bool
    {
        $references = $asset->relationLoaded('references')
            ? $asset->getRelation('references')
            : $asset->references()->get();

        foreach ($references as $reference) {
            if (! $reference instanceof AssetReference) {
                continue;
            }

            $resolver = $this->registry->resolverFor((string) $reference->referencer_type);

            if ($resolver === null) {
                continue;
            }

            if ($resolver->{$ability}($user, $asset, $reference) === true) {
                return true;
            }
        }

        return false;
    }
}
