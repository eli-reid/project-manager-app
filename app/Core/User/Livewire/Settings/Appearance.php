<?php

namespace App\Core\User\Livewire\Settings;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Appearance settings')]
class Appearance extends Component
{
    public function render()
    {
        return view('core-user::livewire.settings.appearance');
    }
}
