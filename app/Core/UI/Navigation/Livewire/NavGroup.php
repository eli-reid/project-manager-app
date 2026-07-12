<?php

declare(strict_types=1);

namespace App\Core\Navigation\Livewire;

use Livewire\Component;

final class NavGroup extends Component
{
    public array $group = [];

    public function mount(array $group): void
    {
        $this->group = $group;
    }

    public function render()
    {
        return view('core-navigation::livewire.components.nav-group', ['group' => $this->group]);
    }
}
