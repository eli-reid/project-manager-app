<?php

declare(strict_types=1);

namespace App\Core\Assets\Services;

use App\Core\Assets\Contracts\AssetAccessResolver;
use InvalidArgumentException;

/**
 * Maps stable referencer keys to the domain resolver that owns their policy.
 */
class AssetReferencerRegistry
{
    /** @var array<string, class-string<AssetAccessResolver>> */
    private array $resolvers = [];

    /** @var array<string, array{max_kilobytes:int, allowed_extensions:array<int, string>}> */
    private array $validationRules = [];

    /**
     * @param  class-string<AssetAccessResolver>  $resolver
     */
    public function register(string $referencerType, string $resolver): void
    {
        if (! is_a($resolver, AssetAccessResolver::class, true)) {
            throw new InvalidArgumentException(
                sprintf('[%s] must implement %s.', $resolver, AssetAccessResolver::class)
            );
        }

        $this->resolvers[$referencerType] = $resolver;
    }

    /**
     * Override upload constraints for a referencer type. Domains should call this
     * when their constraints differ from the Assets default.
     *
     * @param  array{max_kilobytes:int, allowed_extensions:array<int, string>}  $rules
     */
    public function registerValidationRules(string $referencerType, array $rules): void
    {
        $this->validationRules[$referencerType] = $rules;
    }

    public function isRegistered(string $referencerType): bool
    {
        return array_key_exists($referencerType, $this->resolvers);
    }

    public function resolverFor(string $referencerType): ?AssetAccessResolver
    {
        $resolver = $this->resolvers[$referencerType] ?? null;

        if ($resolver === null) {
            return null;
        }

        return app($resolver);
    }

    /**
     * @return array{max_kilobytes:int, allowed_extensions:array<int, string>}|null
     */
    public function validationRulesFor(string $referencerType): ?array
    {
        return $this->validationRules[$referencerType] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function registeredTypes(): array
    {
        return array_keys($this->resolvers);
    }
}
