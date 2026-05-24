<?php

namespace App\Core\Settings\Livewire\Admin\Settings;

use App\Core\Settings\Models\SettingsSqlite;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('core::layouts.settings-admin')]
#[Title('Import Settings')]
class Import extends Component
{
    use AuthorizesRequests;

    public function mount(): void
    {
        $this->authorize('import', SettingsSqlite::class);
    }

    public function render()
    {
        return view('core::livewire.admin.settings.import');
    }
}
