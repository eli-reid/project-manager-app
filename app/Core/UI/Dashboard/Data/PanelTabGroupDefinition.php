<?php

namespace App\Core\UI\Dashboard\Data;

use App\Support\Contracts\ProvidesDomainNavbar;

class PanelTabGroupDefinition
{
    /**
     * @param  array<int, string>  $panelKeys
     * @param  class-string<ProvidesDomainNavbar>  $navbarProvider
     */
    public function __construct(
        public readonly string $key,
        public readonly array $panelKeys,
        public readonly string $navbarProvider,
    ) {}

    /**
     * @return array{key: string, panel_keys: array<int, string>, navbar_provider: class-string<ProvidesDomainNavbar>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'panel_keys' => array_values($this->panelKeys),
            'navbar_provider' => $this->navbarProvider,
        ];
    }
}
