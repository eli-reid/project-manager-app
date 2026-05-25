<?php

namespace App\Core\Identity\Livewire\Layouts;

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Support\Contracts\ProvidesDomainNavbar;
use Illuminate\View\View;
use Livewire\Component;

class AccessAdmin extends Component implements ProvidesDomainNavbar
{
    /**
     * @return array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    public static function navbarItems(): array
    {
        return array_values(array_filter([
            auth()->user()?->can('viewAny', User::class)
                ? [
                    'label' => (string) __('Users'),
                    'href' => route('admin.users.index'),
                    'current' => request()->routeIs('admin.users.*'),
                ]
                : null,
            auth()->user()?->can('viewAny', Role::class)
                ? [
                    'label' => (string) __('Roles'),
                    'href' => route('admin.roles.index'),
                    'current' => request()->routeIs('admin.roles.*'),
                ]
                : null,
            auth()->user()?->can('manage-email-accounts')
                ? [
                    'label' => (string) __('Email Management'),
                    'href' => route('admin.cpanel.manage.dashboard'),
                    'current' => request()->routeIs('admin.cpanel.manage.*'),
                ]
                : null,
        ]));
    }

    public function render(): View
    {
        return view('core-user::livewire.layouts.access-admin');
    }
}
