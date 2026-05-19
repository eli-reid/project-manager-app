<x-slot:headerAction>
    <div class="flex items-center gap-2">
        <span
            wire:dirty
            wire:target="report_date,project_id,custom_project_name,additional_notes,work_performed,materials_used,equipment_used,safety_issues,delays,visitors,onsite_employees"
            class="inline-flex h-8 items-center rounded-full border border-amber-700/60 bg-amber-900/40 px-2.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-amber-200"
        >
            {{ __('Unsaved') }}
        </span>

        <button
            type="button"
            wire:click="saveAsDraft"
            data-mobile-haptic
            class="inline-flex min-h-10 items-center justify-center rounded-xl bg-zinc-100 px-3 text-xs font-semibold text-zinc-900 active:bg-zinc-300"
            wire:loading.class="opacity-60"
            wire:loading.attr="disabled"
            wire:target="saveAsDraft"
        >
            <span wire:loading.remove wire:target="saveAsDraft">{{ $isEdit ? __('Save') : __('Draft') }}</span>
            <span wire:loading wire:target="saveAsDraft">{{ __('Saving...') }}</span>
        </button>
    </div>
</x-slot:headerAction>

<div class="flex flex-col gap-5 px-4 py-5 pb-24">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="saveAsDraft" class="flex flex-col gap-5">
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Report Date') }}</label>
            <input type="date" wire:model="report_date" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none" />
            @error('report_date') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror

            <label class="mb-1.5 mt-4 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Project') }}</label>
            <select wire:model="project_id" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none">
                <option value="">{{ __('Custom Project') }}</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror

            <label class="mb-1.5 mt-4 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Custom Project Name') }}</label>
            <input type="text" wire:model="custom_project_name" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none" />
            @error('custom_project_name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Weather (Auto)') }}</p>
            <p class="mt-1 text-sm text-zinc-300">
                {{ $weather_condition ?: __('Will auto-populate when saving.') }}
                @if ($temperature !== null)
                    ({{ $temperature }} {{ $temperature_unit }})
                @endif
            </p>
            @if (filled($weather_source_location))
                <p class="mt-1 text-xs text-zinc-500">{{ __('Source') }}: {{ $weather_source_location }}</p>
            @endif

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Regular') }}</label>
                    <input type="number" wire:model="total_regular_hours" readonly class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100" />
                </div>
                <div>
                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Overtime') }}</label>
                    <input type="number" wire:model="total_overtime_hours" readonly class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <div class="mb-3 flex items-center justify-between">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Work Performed') }}</p>
                <button type="button" wire:click="addWorkPerformedItem" class="rounded-full border border-zinc-700 px-3 py-1 text-[11px] font-semibold text-zinc-300" data-mobile-haptic>
                    {{ __('Add') }}
                </button>
            </div>

            <div class="flex flex-col gap-3">
                @foreach ($work_performed as $index => $item)
                    <div wire:key="mobile-wp-{{ $index }}" class="rounded-xl border border-zinc-800 bg-zinc-950 px-3 py-3">
                        <input type="text" wire:model="work_performed.{{ $index }}.description" placeholder="{{ __('Work description') }}" class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-zinc-500 focus:outline-none" />

                        <div class="mt-2 grid grid-cols-[1fr_auto] gap-2">
                            <input type="number" min="0" max="24" step="0.25" wire:model="work_performed.{{ $index }}.hours" placeholder="{{ __('Hours') }}" class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none" />
                            <button type="button" wire:click="removeWorkPerformedItem({{ $index }})" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-800/60 bg-rose-900/30 text-rose-300" data-mobile-haptic aria-label="{{ __('Remove item') }}">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>

                        <label class="mt-2 inline-flex items-center gap-2 text-sm text-zinc-300">
                            <input type="checkbox" wire:model="work_performed.{{ $index }}.is_overtime" class="rounded border-zinc-700 bg-zinc-900" />
                            <span>{{ __('Overtime') }}</span>
                        </label>

                        <label class="mb-1.5 mt-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Employees') }}</label>
                        <select multiple wire:model="work_performed.{{ $index }}.employees" class="w-full rounded-xl border border-zinc-700 bg-zinc-900 px-3 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none">
                            @foreach ($employees as $employeeName)
                                <option value="{{ $employeeName }}">{{ $employeeName }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error("work_performed.{$index}.description") <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                    @error("work_performed.{$index}.hours") <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                    @error("work_performed.{$index}.employees") <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                    @error("work_performed.{$index}.employees.*") <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                    @error("work_performed.{$index}.is_overtime") <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Additional Notes') }}</label>
            <textarea rows="4" wire:model="additional_notes" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"></textarea>
            @error('additional_notes') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        @error('dailyReport')
            <p class="text-sm font-medium text-red-400">{{ $message }}</p>
        @enderror

        <div class="flex flex-col gap-2">
            @if ($canSubmit)
                <button
                    type="button"
                    wire:click="saveAndSubmit"
                    class="flex min-h-12 w-full items-center justify-center rounded-2xl bg-zinc-100 text-sm font-semibold text-zinc-900"
                    data-mobile-haptic
                >
                    {{ __('Save & Submit') }}
                </button>
            @endif

            @if ($canDelete)
                <button
                    type="button"
                    wire:click="delete"
                    wire:confirm="{{ __('Delete this daily report? This action cannot be undone.') }}"
                    class="flex min-h-12 w-full items-center justify-center rounded-2xl border border-rose-800/60 bg-rose-900/30 text-sm font-semibold text-rose-300"
                    data-mobile-haptic
                >
                    {{ __('Delete') }}
                </button>
            @endif

            <a
                href="{{ route('dailies.mobile.index') }}"
                wire:navigate
                class="flex min-h-12 items-center justify-center rounded-2xl border border-zinc-700 text-sm font-semibold text-zinc-400"
                data-mobile-haptic
            >
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</div>
