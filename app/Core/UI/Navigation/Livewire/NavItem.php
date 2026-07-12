<?php

declare(strict_types=1);

namespace App\Core\Navigation\Livewire;

use Livewire\Component;

final class NavItem extends Component
{
    public array $item = [];

    public function mount(array $item): void
    {
        $this->item = $item;
    }

    public function render()
    {
        return view('core-navigation::livewire.components.nav-item', ['item' => $this->item]);
    }
}
