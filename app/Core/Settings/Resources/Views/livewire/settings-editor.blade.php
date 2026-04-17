<div wire:init="loadSettings('{{ $group }}')" class="space-y-4">
    @if (!$group)
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-4 py-6 text-center text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            {{ __('Choose a tab above to start editing settings.') }}
        </div>
    @else
        @if ($successMessage)
            <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                {{ $successMessage }}
            </div>
        @endif

        @if ($errorMessage)
            <div class="rounded-lg border border-rose-300 bg-rose-50 px-3 py-2 text-sm text-rose-800 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-200">
                {{ $errorMessage }}
            </div>
        @endif

        <div class="flex items-end justify-between">
            <div>
                <flux:heading size="lg">{{ ucfirst(str_replace('_', ' ', $group)) }} {{ __('Settings') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Manage :count settings in this group', ['count' => count($settingsMetadata)]) }}</flux:text>
            </div>
        </div>

        @if ($group === 'notifications')
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <livewire:app.core.notification.livewire.admin.channel-matrix />
            </div>
        @endif

        @php($shouldRenderGenericForm = ! ($group === 'notifications' && empty($settingsMetadata)))

        @if ($shouldRenderGenericForm)
        <form wire:submit="updateAllSettings" class="space-y-3">
            @forelse($settingsMetadata as $key => $meta)
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <flux:heading size="sm">{{ $meta['display_name'] }}</flux:heading>
                        @if ($meta['is_required'])
                            <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-700 dark:bg-rose-950 dark:text-rose-300">{{ __('Required') }}</span>
                        @endif
                        @if ($meta['encrypted'])
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700 dark:bg-amber-950 dark:text-amber-300">{{ __('Encrypted') }}</span>
                        @endif
                    </div>

                    @if (!empty($meta['description']))
                        <flux:text class="mb-3 text-sm">{{ $meta['description'] }}</flux:text>
                    @endif

                    @if ($meta['type'] === 'textarea')
                        <textarea
                            id="{{ $key }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            wire:model="formData.{{ $key }}"
                            wire:change="updateSetting('{{ $key }}')"
                            rows="4"
                            placeholder="{{ __('Enter value...') }}"
                        ></textarea>
                    @elseif ($meta['type'] === 'select')
                        <select
                            id="{{ $key }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            wire:model="formData.{{ $key }}"
                            wire:change="updateSetting('{{ $key }}')"
                        >
                            <option value="">{{ __('-- Select an option --') }}</option>
                            @if ($meta['options'])
                                @foreach ($meta['options'] as $optionKey => $optionLabel)
                                    <option value="{{ $optionKey }}">{{ $optionLabel }}</option>
                                @endforeach
                            @endif
                        </select>
                    @elseif ($meta['type'] === 'boolean')
                        <select
                            id="{{ $key }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            wire:model="formData.{{ $key }}"
                            wire:change="updateSetting('{{ $key }}')"
                        >
                            <option value="true">{{ __('Enabled') }}</option>
                            <option value="false">{{ __('Disabled') }}</option>
                        </select>
                    @elseif ($meta['type'] === 'password')
                        <div x-data="{ visible: false }" class="space-y-2">
                            <input
                                x-bind:type="visible ? 'text' : 'password'"
                                id="{{ $key }}"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                wire:model="formData.{{ $key }}"
                                placeholder="{{ __('Leave blank to keep current value') }}"
                                autocomplete="off"
                            >
                            <button type="button" x-on:click="visible = !visible" class="text-xs text-zinc-600 underline underline-offset-2 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200">
                                <span x-show="!visible">{{ __('Show value') }}</span>
                                <span x-show="visible">{{ __('Hide value') }}</span>
                            </button>
                        </div>
                    @elseif ($meta['type'] === 'email')
                        <input
                            type="email"
                            id="{{ $key }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            wire:model="formData.{{ $key }}"
                            wire:change="updateSetting('{{ $key }}')"
                            placeholder="user@example.com"
                        >
                    @elseif ($meta['type'] === 'url')
                        <input
                            type="url"
                            id="{{ $key }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            wire:model="formData.{{ $key }}"
                            wire:change="updateSetting('{{ $key }}')"
                            placeholder="https://example.com"
                        >
                    @elseif ($meta['type'] === 'number' || $meta['type'] === 'integer')
                        <input
                            type="number"
                            id="{{ $key }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            wire:model="formData.{{ $key }}"
                            wire:change="updateSetting('{{ $key }}')"
                            placeholder="0"
                        >
                    @else
                        <input
                            type="text"
                            id="{{ $key }}"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                            wire:model="formData.{{ $key }}"
                            wire:change="updateSetting('{{ $key }}')"
                            placeholder="{{ __('Enter value...') }}"
                        >
                    @endif

                    @if (isset($validationErrors[$key]))
                        <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $validationErrors[$key] }}</p>
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    {{ __('No settings found in this group.') }}
                </div>
            @endforelse

            @if (!empty($settingsMetadata))
                <div class="sticky bottom-2 z-10 flex flex-wrap gap-2 rounded-xl border border-zinc-200 bg-white/90 p-3 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/90">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('Save All Changes') }}</span>
                        <span wire:loading>{{ __('Saving...') }}</span>
                    </flux:button>
                    <flux:button type="button" variant="ghost" wire:click="resetForm">
                        {{ __('Reset') }}
                    </flux:button>
                </div>
            @endif
        </form>
        @endif
    @endif
</div>
