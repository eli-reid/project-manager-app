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
        public readonly string $navigationSectionKey = 'workspace',
        public readonly string $navigationSectionLabel = 'Workspace',
        public readonly int $navigationSectionOrder = 10,
        public readonly ?string $navigationGroup = null,
        public readonly bool $registerInNavigation = true,
    ) {
        $this->label = $label !== ''
            ? $label
            : str($key)->replace(['.', '-', '_'], ' ')->headline()->value();
    }

    /**
     * @return array{key: string, component: string, icon: string, sort: int, ability: string, ability_model: string, label: string, description: string, badge: string, navigation_section_key: string, navigation_section_label: string, navigation_section_order: int, navigation_group: ?string, register_in_navigation: bool}
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
            'navigation_section_key' => $this->navigationSectionKey,
            'navigation_section_label' => $this->navigationSectionLabel,
            'navigation_section_order' => $this->navigationSectionOrder,
            'navigation_group' => $this->navigationGroup,
            'register_in_navigation' => $this->registerInNavigation,
        ];
    }
}
