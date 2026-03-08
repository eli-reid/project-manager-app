<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                @can('admin')
                    <flux:sidebar.group expandable heading="Admin" class="grid">
                        <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                            {{ __('Users') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="shield-check" :href="route('admin.roles.index')" :current="request()->routeIs('admin.roles.*')" wire:navigate>
                            {{ __('Roles') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="cog" :href="route('admin.settings.index')" :current="request()->routeIs('admin.settings.*')" data-test="admin-settings-sidebar-main-link">
                            {{ __('Settings') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                <flux:sidebar.item href="#" icon="drafting-compass" :current="request()->routeIs('projects')" wire:navigate>
                    {{ __('My Projects') }}
                </flux:sidebar.item>

                <flux:sidebar.group expandable heading="Time" class="grid">
                    <flux:sidebar.item href="#" icon='clock' :current="request()->routeIs('timecards')">My Timecards</flux:sidebar.item>
                    <flux:sidebar.item href="#" icon='calendar' :current="request()->routeIs('daily')">Daily</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="Stock" class="grid">
                    <flux:sidebar.item href="#" icon='clipboard-pen-line' :current="request()->routeIs('timecards')">Create Order</flux:sidebar.item>
                    <flux:sidebar.item href="#" icon='clipboard-type' :current="request()->routeIs('daily')">My Templates</flux:sidebar.item>
                </flux:sidebar.group>
                <flux:sidebar.group expandable heading="Documents" class="grid">
                    <flux:sidebar.item href="#" icon='clipboard-pen-line' :current="request()->routeIs('timecards')">My Documents</flux:sidebar.item>
                    <flux:sidebar.item href="#" icon='clipboard-type' :current="request()->routeIs('daily')">Upload</flux:sidebar.item>
                </flux:sidebar.group>

            </flux:sidebar.nav>
            <flux:spacer />
            @auth
                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            @else
                <div class="hidden px-4 pb-4 lg:block">
                    <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        {{ __('Log in') }}
                    </a>
                </div>
            @endauth
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            @auth
                <flux:dropdown position="top" align="end">
                    <flux:profile
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <flux:avatar
                                        :name="auth()->user()->name"
                                        :initials="auth()->user()->initials()"
                                    />

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                        <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                            @can('admin')
                                <flux:menu.item :href="route('admin.settings.index')" icon="cog" data-test="admin-settings-link-mobile">
                                    {{ __('Admin Settings') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('admin.users.index')" icon="users" wire:navigate>
                                    {{ __('Admin Users') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('admin.roles.index')" icon="shield-check" wire:navigate>
                                    {{ __('Admin Roles') }}
                                </flux:menu.item>
                            @endcan
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('Log out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            @else
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    {{ __('Log in') }}
                </a>
            @endauth
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
