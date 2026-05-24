@php
    $showWebmailLink = app(\App\Core\Cpanel\Services\CpanelService::class)->isConfigured()
        && filled(trim((string) (auth()->user()?->company_email ?? auth()->user()?->username ?? '')));
@endphp

<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
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
        <flux:menu.separator />
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun" />
            <flux:radio value="dark" icon="moon" />
            <flux:radio value="system" icon="computer-desktop" />
        </flux:radio.group>

        <flux:menu.separator />
        <flux:menu.radio.group>
            @if ($showWebmailLink)
                <flux:menu.item
                    as="button"
                    type="button"
                    icon="envelope"
                    data-test="user-webmail-menu-link"
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

            @can('payroll-stubs.view-own')
                <flux:menu.item :href="route('payroll.history')" icon="wallet" wire:navigate data-test="payroll-menu-link">
                    {{ __('My Payroll') }}
                </flux:menu.item>
            @endcan

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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>