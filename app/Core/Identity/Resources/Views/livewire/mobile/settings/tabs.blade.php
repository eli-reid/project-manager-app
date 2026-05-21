<div class="overflow-x-auto pb-1">
    <nav class="flex min-w-max items-center gap-2" aria-label="{{ __('Mobile settings tabs') }}">
        <a
            href="{{ route('settings.mobile.profile') }}"
            wire:navigate
            class="inline-flex min-h-10 items-center rounded-full border px-4 text-xs font-semibold {{ request()->routeIs('settings.mobile.profile') ? 'border-zinc-500 bg-zinc-800 text-zinc-100' : 'border-zinc-800 bg-zinc-900 text-zinc-300' }}"
        >
            {{ __('Profile') }}
        </a>

        <a
            href="{{ route('settings.mobile.password') }}"
            wire:navigate
            class="inline-flex min-h-10 items-center rounded-full border px-4 text-xs font-semibold {{ request()->routeIs('settings.mobile.password') ? 'border-zinc-500 bg-zinc-800 text-zinc-100' : 'border-zinc-800 bg-zinc-900 text-zinc-300' }}"
        >
            {{ __('Password') }}
        </a>

        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <a
                href="{{ route('settings.mobile.two-factor') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center rounded-full border px-4 text-xs font-semibold {{ request()->routeIs('settings.mobile.two-factor') ? 'border-zinc-500 bg-zinc-800 text-zinc-100' : 'border-zinc-800 bg-zinc-900 text-zinc-300' }}"
            >
                {{ __('2FA') }}
            </a>
        @endif

        <a
            href="{{ route('settings.mobile.notifications') }}"
            wire:navigate
            class="inline-flex min-h-10 items-center rounded-full border px-4 text-xs font-semibold {{ request()->routeIs('settings.mobile.notifications') ? 'border-zinc-500 bg-zinc-800 text-zinc-100' : 'border-zinc-800 bg-zinc-900 text-zinc-300' }}"
        >
            {{ __('Notifications') }}
        </a>

        <a
            href="{{ route('settings.mobile.appearance') }}"
            wire:navigate
            class="inline-flex min-h-10 items-center rounded-full border px-4 text-xs font-semibold {{ request()->routeIs('settings.mobile.appearance') ? 'border-zinc-500 bg-zinc-800 text-zinc-100' : 'border-zinc-800 bg-zinc-900 text-zinc-300' }}"
        >
            {{ __('Appearance') }}
        </a>
    </nav>
</div>
