<?php

namespace App\Core\Dashboard\Data;

class WidgetDefinition
{
    public readonly string $title;

    public function __construct(
        public readonly string $key,
        public readonly string $component,
        public readonly string $section = 'primary',
        public readonly int $sort = 100,
        public readonly string $span = 'third',
        public readonly string $ability = '',
        public readonly string $abilityModel = '',
        string $title = '',
        public readonly string $description = '',
    ) {
        $this->title = $title !== ''
            ? $title
            : str($key)->replace(['.', '-', '_'], ' ')->headline()->value();
    }

    /**
     * @param  array{key: string, component: string, section?: string, sort?: int, span?: string, ability?: string, ability_model?: string, title?: string, description?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        $key = (string) ($data['key'] ?? '');

        return new self(
            key: $key,
            component: (string) ($data['component'] ?? ''),
            section: (string) ($data['section'] ?? 'primary'),
            sort: (int) ($data['sort'] ?? 100),
            span: (string) ($data['span'] ?? 'third'),
            ability: (string) ($data['ability'] ?? ''),
            abilityModel: (string) ($data['ability_model'] ?? ''),
            title: (string) ($data['title'] ?? str($key)->replace(['.', '-', '_'], ' ')->headline()->value()),
            description: (string) ($data['description'] ?? ''),
        );
    }

    /**
     * @return array{key: string, component: string, section: string, sort: int, span: string, ability: string, ability_model: string, title: string, description: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'component' => $this->component,
            'section' => $this->section,
            'sort' => $this->sort,
            'span' => $this->span,
            'ability' => $this->ability,
            'ability_model' => $this->abilityModel,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
