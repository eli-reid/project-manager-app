<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class DomainNavbar extends Component
{
    /**
     * @var array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    public array $items = [];

    /**
     * @param  array<int, array{label: string, href: string, current: bool, visible?: bool}>  $items
     */
    public function mount(array $items = []): void
    {
        $this->items = $items;
    }

    public function render(): View
    {
        return view('livewire.layouts.domain-navbar');
    }
}
