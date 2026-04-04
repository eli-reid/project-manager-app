<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php
            $showWebmailLink = auth()->check()
                && app(\App\Core\Cpanel\Services\CpanelService::class)->isConfigured()
                && filled(trim((string) (auth()->user()?->company_email ?? auth()->user()?->username ?? '')));
        @endphp

        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:inline-flex"/>
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                

                @include('partials.nav.sidebar-user-nav')
                

                @include('partials.nav.sidebar-admin-nav')

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
                            @if ($showWebmailLink)
                                <flux:menu.item :href="route('webmail.redirect')" icon="envelope" target="_blank" rel="noopener noreferrer" data-test="user-webmail-menu-link-mobile">
                                    {{ __('Webmail') }}
                                </flux:menu.item>
                            @endif

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
                                @can('deleteAny', \App\Domains\Documents\Models\Document::class)
                                    <flux:menu.item :href="route('admin.documents.index')" icon="folder" wire:navigate>
                                        {{ __('Admin Documents') }}
                                    </flux:menu.item>
                                @endcan
                            @endcan

                            @can('viewAny', \App\Core\Scheduler\Models\ScheduledTask::class)
                                <flux:menu.item :href="route('admin.scheduler.tasks.index')" icon="clock" wire:navigate data-test="admin-scheduler-link-mobile">
                                    {{ __('Scheduler') }}
                                </flux:menu.item>
                            @endcan

                            @can('queue.viewAny')
                                <flux:menu.item :href="route('admin.queue.index')" icon="server-stack" wire:navigate data-test="admin-queue-link-mobile">
                                    {{ __('Queue') }}
                                </flux:menu.item>
                            @endcan

                            @can('viewAny', \App\Core\Announcement\Models\Announcement::class)
                                <flux:menu.item :href="route('admin.announcements.index')" icon="megaphone" wire:navigate data-test="admin-announcements-link-mobile">
                                    {{ __('Announcements') }}
                                </flux:menu.item>
                            @endcan

                            @can('viewAny', \App\Domains\Timecards\Models\Timecard::class)
                                <flux:menu.item :href="route('timecards.index')" icon="clock" wire:navigate>
                                    {{ __('My Timecards') }}
                                </flux:menu.item>
                            @endcan

                            @can('viewAll', \App\Domains\Timecards\Models\Timecard::class)
                                <flux:menu.item :href="route('admin.timecards.index')" icon="clock" wire:navigate data-test="timecards-link-mobile">
                                    {{ __('All Timecards') }}
                                </flux:menu.item>
                            @endcan

                            @can('viewAny', \App\Domains\Invoices\Models\Invoice::class)
                                <flux:menu.item :href="route('admin.invoices.index')" icon="document-text" wire:navigate data-test="admin-invoices-link-mobile">
                                    {{ __('Invoices') }}
                                </flux:menu.item>
                            @endcan

                            @can('reports.financial.view')
                                <flux:menu.item :href="route('reports.financial.index')" icon="document-text" wire:navigate data-test="reports-link-mobile">
                                    {{ __('Reports') }}
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
