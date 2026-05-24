<section class="space-y-4 px-4 py-4">
    <livewire:settings::mobile.settings-tabs />

    <div class="space-y-1">
        <flux:heading size="lg">{{ __('Appearance') }}</flux:heading>
        <flux:text class="text-zinc-400">{{ __('Update the appearance settings for your account') }}</flux:text>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-4">
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="w-full">
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
        </flux:radio.group>
    </div>
</section>
