<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Set a new password')"
        :description="__('You need to change your temporary password before you can continue using the app.')"
    />

    <x-auth-session-status class="text-center" :status="session('status')" />

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-100">
        {{ __('Use a password you have not used elsewhere. Once this is saved, you will be sent back to the page you originally tried to open.') }}
    </div>

    <form wire:submit="updatePassword" class="flex flex-col gap-6">
        <flux:field>
            <flux:label>{{ __('New password') }}</flux:label>
            <flux:input wire:model="password" type="password" autocomplete="new-password" viewable />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Confirm password') }}</flux:label>
            <flux:input wire:model="password_confirmation" type="password" autocomplete="new-password" viewable />
        </flux:field>

        <flux:button variant="primary" type="submit" class="w-full">
            {{ __('Update password') }}
        </flux:button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <flux:button type="submit" variant="filled" class="w-full">
            {{ __('Log out') }}
        </flux:button>
    </form>
</div>
