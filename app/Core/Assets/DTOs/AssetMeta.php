<?php

<<<<<<< HEAD
declare(strict_types=1);

=======
>>>>>>> production
namespace App\Core\Assets\DTOs;

use DateTimeInterface;

<<<<<<< HEAD
/**
 * Optional storage hints supplied by a calling domain.
 *
 * A null property means "no preference"; the orchestrator falls back to the
 * corresponding application setting.
 */
=======
>>>>>>> production
final class AssetMeta
{
    public function __construct(
        public readonly ?string $folderPath = null,
        public readonly ?string $disk = null,
        public readonly ?string $visibility = null,
<<<<<<< HEAD
        public readonly ?bool $dedupeByHash = null,
        public readonly ?DateTimeInterface $expiresAt = null,
    ) {}
=======
        public readonly ?string $contentHash = null,
        public readonly ?bool $dedupeByHash = null,
        public readonly ?DateTimeInterface $expiresAt = null,
        public readonly ?int $cacheTtlSeconds = null,
        public readonly ?string $storageClass = null,
    ) {
    }
>>>>>>> production

    /**
     * Create from loose array keys. Accepts snake_case or camelCase.
     *
<<<<<<< HEAD
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $read = static function (array $names) use ($data): mixed {
            foreach ($names as $name) {
                if (array_key_exists($name, $data)) {
                    return $data[$name];
=======
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $get = fn(array $keys) => null;

        $normalize = function (array $names) use ($data) {
            foreach ($names as $n) {
                if (array_key_exists($n, $data)) {
                    return $data[$n];
>>>>>>> production
                }
            }

            return null;
        };

<<<<<<< HEAD
        $expiresAt = $read(['expiresAt', 'expires_at']);

        if (is_string($expiresAt)) {
            try {
                $expiresAt = new \DateTimeImmutable($expiresAt);
            } catch (\Throwable) {
                $expiresAt = null;
            }
        }

        $folderPath = $read(['folderPath', 'folder_path']);
        $disk = $read(['disk']);
        $visibility = $read(['visibility']);
        $dedupe = $read(['dedupeByHash', 'dedupe_by_hash']);

        return new self(
            folderPath: is_string($folderPath) ? $folderPath : null,
            disk: is_string($disk) ? $disk : null,
            visibility: is_string($visibility) ? $visibility : null,
            dedupeByHash: $dedupe === null ? null : (bool) $dedupe,
            expiresAt: $expiresAt instanceof DateTimeInterface ? $expiresAt : null,
=======
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
>>>>>>> production
        );
    }
}
