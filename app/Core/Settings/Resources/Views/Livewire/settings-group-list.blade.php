<div class="space-y-3">
    @if ($asNavbar)
        @if ($groups->isNotEmpty())
            <flux:navbar class="flex flex-wrap items-center gap-2">
                @foreach ($groups as $group)
                    @php($isActive = $selectedGroup === $group->group)
                    <flux:navbar.item
                        href="#"
                        :current="$isActive"
                        wire:click.prevent="selectGroup('{{ $group->group }}')"
                    >
                        {{ $this->getGroupDisplayName($group->group) }}
                    </flux:navbar.item>
                @endforeach
            </flux:navbar>
        @endif
    @else
    @if ($groups->isNotEmpty())
        <div class="lg:hidden">
            <label for="settings-group-select" class="mb-1 block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ __('Group') }}
            </label>
            <select
                id="settings-group-select"
                wire:model.live="selectedGroup"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
            >
                @foreach ($groups as $group)
                    <option value="{{ $group->group }}">
                        {{ $this->getGroupDisplayName($group->group) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="hidden lg:block">
            <div class="space-y-2" role="tablist" aria-label="Setting groups">
                @foreach ($groups as $group)
                    @php($isActive = $selectedGroup === $group->group)
                    <button
                        type="button"
                        wire:click="selectGroup('{{ $group->group }}')"
                        role="tab"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        class="w-full rounded-lg border px-3 py-2 text-left text-sm transition {{ $isActive
                            ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900'
                            : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                    >
                        <span class="font-medium">{{ $this->getGroupDisplayName($group->group) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            {{ __('No setting groups found.') }}
        </div>
    @endif
    @endif
</div>
