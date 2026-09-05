<?php

declare(strict_types=1);

namespace App\Core\Assets\DTOs;

use DateTimeInterface;

/**
 * Optional storage hints supplied by a calling domain.
 *
 * A null property means "no preference"; the orchestrator falls back to the
 * corresponding application setting.
 */
final class AssetMeta
{
    public function __construct(
        public readonly ?string $folderPath = null,
        public readonly ?string $disk = null,
        public readonly ?string $visibility = null,
        public readonly ?bool $dedupeByHash = null,
        public readonly ?DateTimeInterface $expiresAt = null,
    ) {}

    /**
     * Create from loose array keys. Accepts snake_case or camelCase.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $read = static function (array $names) use ($data): mixed {
            foreach ($names as $name) {
                if (array_key_exists($name, $data)) {
                    return $data[$name];
                }
            }

            return null;
        };

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
        );
    }
}
