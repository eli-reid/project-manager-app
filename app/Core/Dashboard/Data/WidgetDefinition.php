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
        // Optional explicit grid size (in grid columns / rows). If width is 0 the
        // value is derived from the legacy `span` value for backward compatibility.
        public readonly int $width = 0,
        public readonly int $height = 1,
    ) {
        $this->title = $title !== ''
            ? $title
            : str($key)->replace(['.', '-', '_'], ' ')->headline()->value();
    }

    /**
     * @param  array{key: string, component: string, section?: string, sort?: int, span?: string, ability?: string, ability_model?: string, title?: string, description?: string, width?: int, height?: int}  $data
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
            width: (int) ($data['width'] ?? 0),
            height: (int) ($data['height'] ?? 1),
        );
    }

    /**
     * @return array{key: string, component: string, section: string, sort: int, span: string, ability: string, ability_model: string, title: string, description: string, width: int, height: int}
     */
    public function toArray(): array
    {
        // Determine effective width based on explicit width or legacy span mapping.
        $effectiveWidth = $this->width > 0 ? $this->width : match ($this->span) {
            'full' => 6,
            'half' => 3,
            default => 2,
        };

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
            'width' => $effectiveWidth,
            'height' => $this->height,
        ];
    }
}
