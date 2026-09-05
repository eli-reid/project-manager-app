<div
    class="contents"
    x-data="{
        openMailboxWindow(payload) {
            if (!payload || typeof payload !== 'object') {
                return;
            }

            if (payload.mode === 'post_handshake' && payload.login_url && payload.session) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = payload.login_url;
                form.target = '_blank';

                const sessionInput = document.createElement('input');
                sessionInput.type = 'hidden';
                sessionInput.name = 'session';
                sessionInput.value = payload.session;

                form.appendChild(sessionInput);
                document.body.appendChild(form);
                form.submit();
                form.remove();

                return;
            }

            if (payload.url) {
                window.open(payload.url, '_blank', 'noopener,noreferrer');
            }
        }
    }"
    x-on:open-webmail.window="openMailboxWindow($event.detail)"
>
    <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header class="gap-2">
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" class="min-w-0 flex-1" wire:navigate />
            <flux:sidebar.collapse class="shrink-0 lg:inline-flex"/>
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                {{ __('Dashboard') }}
            </flux:sidebar.item>

            <livewire:nav.sidebar-user-nav />

            <livewire:nav.sidebar-admin-nav />
        </flux:sidebar.nav>

        <flux:spacer />

        @auth
            <div class="hidden lg:block">
                <livewire:auth.user.desktop-user-menu lazy />
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

        <livewire:auth.user.mobile-user-menu lazy />
    </flux:header>
</div>