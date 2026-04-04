<?php

namespace App\Core\Settings\Contracts;

interface SettingsRegistryContract
{
    /**
     * @param  array<int, array<string, mixed>>  $definitions
     */
    public function registerDefinitions(string $domain, array $definitions): void;

    public function registerConfigFile(string $domain, string $configFile): void;

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function definitionsByDomain(): array;
}
