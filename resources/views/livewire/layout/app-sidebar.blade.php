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
            <div class="hidden lg:block">
                @include('auth-user::partials.desktop-user-menu')
            </div>
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

        @include('auth-user::partials.mobile-user-menu')
    </flux:header>
</div>