<?php

declare(strict_types=1);

namespace App\Core\Navigation\DTO;

final class NavGroup extends AbstractNavGroup
{
    public readonly int $order;

    public function __construct(string $id, string $label, array $items = [], string $icon = '', bool $collapsed = false, int $order = 0)
    {
        parent::__construct($id, $label, $items, $icon, $collapsed);
        $this->order = $order;
    }
}
