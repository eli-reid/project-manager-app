<?php

declare(strict_types=1);

namespace App\Core\UI\Navigation\DTO;

use App\Core\UI\Navigation\Contracts\NavGroupInterface;
use Illuminate\Support\Facades\Auth;

abstract class AbstractNavGroup implements NavGroupInterface
{
    public readonly string $id;

    public readonly string $label;

    private readonly array $items;

    public readonly string $icon;

    public readonly bool $collapsed;

    public function __construct(string $id, string $label, array $items, string $icon, bool $collapsed)
    {
        $this->id = $id;
        $this->label = $label;
        $this->items = $items;
        $this->icon = $icon;
        $this->collapsed = $collapsed;
    }

    public function items(): array
    {
        $userAllowedItems = [];
        foreach ($this->items as $item) {
            if (Auth::user()->can('view', $item)) {
                $userAllowedItems[] = $item;
            }
        }

        return $userAllowedItems;
    }
}
