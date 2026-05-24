<?php

namespace App\Livewire\Layouts;

use Illuminate\View\View;
use Livewire\Component;

class DomainLayout extends Component
{
    public ?string $title = null;

    /**
     * @var array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    public array $navbarItems = [];

    /**
     * @param  array<int, array{label: string, href: string, current: bool, visible?: bool}>  $navbarItems
     */
    public function mount(?string $title = null, array $navbarItems = []): void
    {
        $this->title = $title;
        $this->navbarItems = $navbarItems;
    }

    public function render(): View
    {
        return view('livewire.layouts.domain-layout');
    }
}
