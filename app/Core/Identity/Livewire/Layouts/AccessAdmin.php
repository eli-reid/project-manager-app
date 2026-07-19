<?php

namespace App\Core\Identity\Livewire\Layouts;

use App\Core\Auth\Role\Models\Role;
use App\Core\Identity\Models\User;
use App\Support\Contracts\ProvidesDomainNavbar;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class AccessAdmin extends Component implements ProvidesDomainNavbar
{
    /**
     * @return array<int, array{label: string, href: string, current: bool, visible?: bool}>
     */
    public static function navbarItems(): array
    {
        $isDashboardPanel = request()->routeIs('dashboard');
        $currentPanel = (string) request()->query('panel', '');
        $user = Auth::user();

        return array_values(array_filter([
            $user?->can('viewAny', User::class)
                ? [
                    'label' => (string) __('Users'),
                    'href' => route('dashboard', ['panel' => 'access-users']),
                    'current' => request()->routeIs('admin.users.*') || ($isDashboardPanel && $currentPanel === 'access-users'),
                ]
                : null,
            $user?->can('viewAny', Role::class)
                ? [
                    'label' => (string) __('Roles'),
                    'href' => route('dashboard', ['panel' => 'access-roles']),
                    'current' => request()->routeIs('admin.roles.*') || ($isDashboardPanel && $currentPanel === 'access-roles'),
                ]
                : null,
            $user?->can('manage-email-accounts')
                ? [
                    'label' => (string) __('Email Management'),
                    'href' => route('dashboard', ['panel' => 'access-email-management']),
                    'current' => request()->routeIs('admin.cpanel.manage.*') || ($isDashboardPanel && $currentPanel === 'access-email-management'),
                ]
                : null,
        ]));
    }

    public function render(): View
    {
        return view('core-user::livewire.layouts.access-admin');
    }
}
