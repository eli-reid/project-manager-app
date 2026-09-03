<?php

declare(strict_types=1);

namespace App\Core\Assets\DTOs;

/**
 * Identifies the domain record that owns a reference to an asset.
 *
 * `referencerType` is a stable registry key (for example `documents`), never a
 * fully-qualified class name, so namespace refactors cannot invalidate stored rows.
 */
final class AssetReferenceTarget
{
    public const ROLE_PRIMARY = 'primary';

    public function __construct(
        public readonly string $referencerType,
        public readonly string $referencerId,
        public readonly string $role = self::ROLE_PRIMARY,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toAttributes(): array
    {
        return [
            'referencer_type' => $this->referencerType,
            'referencer_id' => $this->referencerId,
            'role' => $this->role,
        ];
    }
}
