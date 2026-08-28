<?php

namespace App\Domains\Clients\Livewire\Layouts;

use App\Domains\Clients\Models\Client;
use App\Support\Contracts\ProvidesDomainNavbar;
use Illuminate\View\View;
use Livewire\Component;

class ClientManagementAdmin extends Component implements ProvidesDomainNavbar
{
    /**
     * @return array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    public static function navbarItems(): array
    {
        return array_values(array_filter([
            auth()->user()?->can('viewAny', Client::class)
                ? [
                    'label' => (string) __('Clients'),
                    'href' => route('admin.clients.index'),
                    'current' => request()->routeIs('admin.clients.*'),
                ]
                : null,
        ]));
    }

    public function render(): View
    {
        return view('clients::livewire.layouts.client-management-admin');
    }
}
