<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:inline-flex"/>
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                @can('admin')
                    <flux:sidebar.item icon="shield-check" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.settings.*')" wire:navigate data-test="admin-settings-sidebar-main-link">
                        {{ __('Access') }}
                    </flux:sidebar.item>
                @endcan

                @can('viewAny', \App\Core\Scheduler\Models\ScheduledTask::class)
                    <flux:sidebar.item icon="clock" :href="route('admin.scheduler.tasks.index')" :current="request()->routeIs('admin.scheduler.tasks.*')" wire:navigate data-test="admin-scheduler-sidebar-main-link">
                        {{ __('Scheduler') }}
                    </flux:sidebar.item>
                @endcan

                @can('viewAny', \App\Core\Announcement\Models\Announcement::class)
                    <flux:sidebar.item icon="megaphone" :href="route('admin.announcements.index')" :current="request()->routeIs('admin.announcements.*')" wire:navigate data-test="admin-announcements-sidebar-main-link">
                        {{ __('Announcements') }}
                    </flux:sidebar.item>
                @endcan

                @can('viewAny', \App\Domains\Projects\Models\Project::class)
                    <flux:sidebar.item icon="drafting-compass" :href="route('admin.projects.index')" :current="request()->routeIs('admin.projects.*')" wire:navigate>
                        {{ __('Projects') }}
                    </flux:sidebar.item>
                @endcan

                @can('viewAny', \App\Domains\Clients\Models\Client::class)
                    <flux:sidebar.item icon="building-2" :href="route('admin.clients.index')" :current="request()->routeIs('admin.clients.*')" wire:navigate>
                        {{ __('Clients') }}
                    </flux:sidebar.item>
                @endcan

                @if (auth()->user()?->hasPermission('tasks.view') || auth()->user()?->hasPermission('task-categories.view') || auth()->user()?->hasPermission('task-templates.view'))
                    <flux:sidebar.item icon="check-circle" :href="route('admin.tasks.index')" :current="request()->routeIs('admin.tasks.*') || request()->routeIs('admin.task-categories.*') || request()->routeIs('admin.task-templates.*')" wire:navigate>
                        {{ __('Tasks') }}
                    </flux:sidebar.item>
                @endif

                <flux:sidebar.item href="#" icon="clock" :current="false">{{ __('Time') }}</flux:sidebar.item>
                <flux:sidebar.item href="#" icon="clipboard-pen-line" :current="false">{{ __('Stock') }}</flux:sidebar.item>
                <flux:sidebar.item href="#" icon="folder" :current="false">{{ __('Documents') }}</flux:sidebar.item>

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

                            @can('viewAny', \App\Core\Scheduler\Models\ScheduledTask::class)
                                <flux:menu.item :href="route('admin.scheduler.tasks.index')" icon="clock" wire:navigate data-test="admin-scheduler-link-mobile">
                                    {{ __('Scheduler') }}
                                </flux:menu.item>
                            @endcan

                            @can('viewAny', \App\Core\Announcement\Models\Announcement::class)
                                <flux:menu.item :href="route('admin.announcements.index')" icon="megaphone" wire:navigate data-test="admin-announcements-link-mobile">
                                    {{ __('Announcements') }}
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

        @isset($domainNavbar)
            <div class="sticky top-0 z-30 w-full border-b border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                {{ $domainNavbar }}
            </div>
        @endisset

        {{ $slot }}

        @fluxScripts
    </body>
</html>
