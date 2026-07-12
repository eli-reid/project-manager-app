<?php

namespace App\Core\UI\Dashboard\Data;

class PanelDefinition
{
    public readonly string $label;

    public function __construct(
        public readonly string $key,
        public readonly string $component,
        public readonly string $icon = '',
        public readonly int $sort = 100,
        public readonly string $ability = '',
        public readonly string $abilityModel = '',
        string $label = '',
        public readonly string $description = '',
        public readonly string $badge = '',
    ) {
        $this->label = $label !== ''
            ? $label
            : str($key)->replace(['.', '-', '_'], ' ')->headline()->value();
    }

    /**
     * @return array{key: string, component: string, icon: string, sort: int, ability: string, ability_model: string, label: string, description: string, badge: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'component' => $this->component,
            'icon' => $this->icon,
            'sort' => $this->sort,
            'ability' => $this->ability,
            'ability_model' => $this->abilityModel,
            'label' => $this->label,
            'description' => $this->description,
            'badge' => $this->badge,
        ];
    }
}
