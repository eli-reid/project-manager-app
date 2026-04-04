<?php

namespace App\Core\Settings\Services;

use App\Core\Settings\Contracts\DomainSettingsProvider;
use Illuminate\Support\Facades\Log;

class SettingsRegistry
{
    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    private array $definitionsByDomain = [];

    /**
     * @param  array<int, array<string, mixed>>  $definitions
     */
    public function registerDefinitions(string $domain, array $definitions): void
    {
        if (! isset($this->definitionsByDomain[$domain])) {
            $this->definitionsByDomain[$domain] = [];
        }

        $this->definitionsByDomain[$domain] = [
            ...$this->definitionsByDomain[$domain],
            ...$definitions,
        ];
    }

    public function registerConfigFile(string $domain, string $configFile): void
    {
        if (! file_exists($configFile)) {
            return;
        }

        $payload = require $configFile;
        $definitions = $this->resolvePayload($payload, $domain);

        $this->registerDefinitions($domain, $definitions);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function definitionsByDomain(): array
    {
        return $this->definitionsByDomain;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolvePayload(mixed $payload, string $domain): array
    {
        if (is_string($payload) && class_exists($payload) && is_subclass_of($payload, DomainSettingsProvider::class)) {
            /** @var class-string<DomainSettingsProvider> $payload */
            return $payload::settings();
        }

        if (is_array($payload) && isset($payload['settings']) && is_array($payload['settings'])) {
            return $payload['settings'];
        }

        if (is_array($payload)) {
            return $payload;
        }

        Log::warning('Invalid domain settings payload registered.', [
            'domain' => $domain,
            'payload_type' => gettype($payload),
        ]);

        return [];
    }
}
