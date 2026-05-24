<div>
    <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header class="gap-2">
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" class="min-w-0 flex-1" wire:navigate />
            <flux:sidebar.collapse class="shrink-0 lg:inline-flex"/>
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
                            <flux:menu.item
                                as="button"
                                type="button"
                                icon="envelope"
                                data-test="user-webmail-menu-link-mobile"
                                data-webmail-launcher
                                data-webmail-session-endpoint="{{ route('webmail.session') }}"
                                data-webmail-fallback-url="{{ route('webmail.redirect') }}"
                            >
                                {{ __('Webmail') }}
                            </flux:menu.item>
                        @endif

                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>

                        @can('viewAny', \App\Domains\Timecards\Models\Timecard::class)
                            <flux:menu.item :href="route('timecards.index')" icon="clock" wire:navigate>
                                {{ __('My Timecards') }}
                            </flux:menu.item>
                        @endcan

                        @can('payroll-stubs.view-own')
                            <flux:menu.item :href="route('payroll.history')" icon="wallet" wire:navigate data-test="payroll-link-mobile">
                                {{ __('My Payroll') }}
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
</div>