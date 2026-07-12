<?php

declare(strict_types=1);

namespace App\Core\UI\Navigation\Contracts;

use App\Core\UI\Navigation\DTO\NavItem;

interface NavGroupInterface
{
    /**
     * Returns the navigation items belonging to this group.
     *
     * @return array<NavItem>
     */
    public function items(): array;
}
