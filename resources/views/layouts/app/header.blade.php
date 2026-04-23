<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>

                @can('viewAny', \App\Domains\Timecards\Models\Timecard::class)
                    <flux:navbar.item
                        icon="clock"
                        :href="auth()->user()?->hasPermission('timecards.view-all') ? route('admin.timecards.index') : route('timecards.index')"
                        :current="request()->routeIs('admin.timecards.*') || request()->routeIs('timecards.*')"
                        wire:navigate
                        data-test="timecards-navbar-link"
                    >
                        {{ __('My Timecards') }}
                    </flux:navbar.item>
                @endcan

                @can('create', \App\Domains\Timecards\Models\Timecard::class)
                    <flux:navbar.item
                        icon="plus"
                        :href="route('timecards.create')"
                        :current="request()->routeIs('timecards.create')"
                        wire:navigate
                        data-test="timecards-create-navbar-link"
                    >
                        {{ __('New Timecard') }}
                    </flux:navbar.item>
                @endcan
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="h-10! [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header class="gap-2">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" class="min-w-0 flex-1" wire:navigate />
                <flux:sidebar.collapse class="shrink-0 in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>

                    @can('viewAny', \App\Domains\Timecards\Models\Timecard::class)
                        <flux:sidebar.item
                            icon="clock"
                            :href="auth()->user()?->hasPermission('timecards.view-all') ? route('admin.timecards.index') : route('timecards.index')"
                            :current="request()->routeIs('admin.timecards.*') || request()->routeIs('timecards.*')"
                            wire:navigate
                            data-test="timecards-sidebar-link-mobile"
                        >
                            {{ __('My Timecards') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('create', \App\Domains\Timecards\Models\Timecard::class)
                        <flux:sidebar.item
                            icon="plus"
                            :href="route('timecards.create')"
                            :current="request()->routeIs('timecards.create')"
                            wire:navigate
                            data-test="timecards-create-sidebar-link-mobile"
                        >
                            {{ __('New Timecard') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('admin')
                        <flux:sidebar.group expandable heading="Admin" class="grid">
                            <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                                {{ __('Users') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="shield-check" :href="route('admin.roles.index')" :current="request()->routeIs('admin.roles.*')" wire:navigate>
                                {{ __('Roles') }}
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="cog" :href="route('admin.settings.index')" :current="request()->routeIs('admin.settings.*')" data-test="admin-settings-sidebar-link">
                                {{ __('Settings') }}
                            </flux:sidebar.item>
                        </flux:sidebar.group>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
