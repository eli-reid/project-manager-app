<?php

namespace App\Domains\Assets\DTOs;

use DateTimeInterface;

final class AssetMeta
{
    public function __construct(
        public readonly ?string $folderPath = null,
        public readonly ?string $disk = null,
        public readonly ?string $visibility = null,
        public readonly ?string $contentHash = null,
        public readonly ?bool $dedupeByHash = null,
        public readonly ?DateTimeInterface $expiresAt = null,
        public readonly ?int $cacheTtlSeconds = null,
        public readonly ?string $storageClass = null,
    ) {
    }

    /**
     * Create from loose array keys. Accepts snake_case or camelCase.
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $get = fn(array $keys) => null;

        $normalize = function (array $names) use ($data) {
            foreach ($names as $n) {
                if (array_key_exists($n, $data)) {
                    return $data[$n];
                }
            }

            return null;
        };

        $folder = $normalize(['folderPath', 'folder_path']);
        $disk = $normalize(['disk']);
        $visibility = $normalize(['visibility']);
        $contentHash = $normalize(['contentHash', 'content_hash']);
        $dedupe = $normalize(['dedupeByHash', 'dedupe_by_hash']);
        $expires = $normalize(['expiresAt', 'expires_at']);
        $cache = $normalize(['cacheTtlSeconds', 'cache_ttl_seconds']);
        $storageClass = $normalize(['storageClass', 'storage_class']);

        if (is_string($expires)) {
            try {
                $expires = new \DateTimeImmutable($expires);
            } catch (\Throwable $e) {
                $expires = null;
            }
        }

        return new self(
            folderPath: is_string($folder) ? $folder : null,
            disk: is_string($disk) ? $disk : null,
            visibility: is_string($visibility) ? $visibility : null,
            contentHash: is_string($contentHash) ? $contentHash : null,
            dedupeByHash: is_null($dedupe) ? null : (bool) $dedupe,
            expiresAt: $expires instanceof DateTimeInterface ? $expires : null,
            cacheTtlSeconds: is_null($cache) ? null : (int) $cache,
            storageClass: is_string($storageClass) ? $storageClass : null,
        );
    }
}
