<?php

declare(strict_types=1);

namespace App\Core\UI\Navigation\DTO;

final class NavItem
{
    public readonly string $id;

    public readonly string $label;

    public readonly ?string $icon;

    public readonly ?string $url;

    public readonly ?string $route;

    public readonly ?string $group;

    public readonly int $order;

    public readonly bool $active;

    public readonly bool $visible;

    public readonly array $permissions;

    public readonly NavSectionEnum $section;

    public readonly array $meta;

    public function __construct(
        string $id,
        string $label,
        ?string $icon = null,
        ?string $url = null,
        ?string $route = null,
        ?string $group = null,
        int $order = 0,
        bool $active = false,
        bool $visible = true,
        array $permissions = [],
        NavSectionEnum $section = NavSectionEnum::USER,
        array $meta = []
    ) {
        $this->id = $id;
        $this->label = $label;
        $this->icon = $icon;
        $this->url = $url;
        $this->route = $route;
        $this->group = $group;
        $this->order = $order;
        $this->active = $active;
        $this->visible = $visible;
        $this->permissions = $permissions;
        $this->section = $section;
        $this->meta = $meta;
    }
}
