<div class="space-y-3">
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
                        {{ $this->getGroupDisplayName($group->group) }} ({{ $this->getGroupCount($group->group) }})
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
                        class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm transition {{ $isActive
                            ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900'
                            : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                    >
                        <span class="font-medium">{{ $this->getGroupDisplayName($group->group) }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $isActive ? 'bg-white/20 text-white dark:bg-zinc-900/20 dark:text-zinc-900' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                            {{ $this->getGroupCount($group->group) }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            {{ __('No setting groups found.') }}
        </div>
    @endif
</div>
