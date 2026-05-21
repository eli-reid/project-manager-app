<?php

namespace App\Core\Identity\Livewire\Settings;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Appearance settings')]
class Appearance extends Component
{
    public function render()
    {
        $view = request()->routeIs('settings.mobile.*')
            ? view('core-user::livewire.mobile.settings.appearance')
            : view('core-user::livewire.settings.appearance');

        if (request()->routeIs('settings.mobile.*')) {
            return $view->layout('layouts.mobile', ['title' => __('Appearance settings')]);
        }

        return $view;
    }
}
