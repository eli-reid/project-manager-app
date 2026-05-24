<?php

namespace App\Livewire\Layouts;

use App\Livewire\Layouts\Contracts\ProvidesDomainNavbar;
use Illuminate\View\View;
use Livewire\Component;

class SettingsAdmin extends Component implements ProvidesDomainNavbar
{
    /**
     * @return array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    public static function navbarItems(): array
    {
        return [];
    }

    public function render(): View
    {
        return view('core::livewire.layouts.settings-admin');
    }
}
