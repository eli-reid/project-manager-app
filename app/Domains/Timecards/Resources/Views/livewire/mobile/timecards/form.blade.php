@php
    $weekStart = \Illuminate\Support\Carbon::parse($week_starting);
@endphp

<x-slot:headerAction>
    <button
        type="submit"
        form="mobile-timecard-form"
        data-mobile-haptic
        class="inline-flex min-h-10 items-center justify-center rounded-xl bg-zinc-100 px-3 text-xs font-semibold text-zinc-900 active:bg-zinc-300"
        wire:loading.class="opacity-60"
        wire:loading.attr="disabled"
    >
        <span wire:loading.remove>{{ $isEdit ? __('Save') : __('Create') }}</span>
        <span wire:loading>{{ __('Saving…') }}</span>
    </button>
</x-slot:headerAction>

<div class="flex flex-col gap-5 px-4 py-5 pb-24">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-3">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Week Range') }}</p>
        <p class="mt-1 text-sm font-semibold text-zinc-100">
            {{ $weekStart->copy()->startOfWeek()->format('M j') }} - {{ $weekStart->copy()->endOfWeek()->format('M j, Y') }}
        </p>
        <p class="mt-1 text-xs text-zinc-400">{{ __('Tap a quick hour chip for faster entry.') }}</p>
    </div>

    {{-- Leave Balances --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl border border-emerald-900/60 bg-emerald-950/30 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-400">{{ __('Sick') }}</p>
            <p class="mt-1 text-lg font-semibold text-emerald-100">{{ number_format((float) data_get($leaveBalances, 'sick.remaining', 0), 2) }} <span class="text-sm font-normal text-emerald-300/70">{{ __('hrs') }}</span></p>
            <p class="mt-0.5 text-[11px] text-emerald-300/60">{{ __('Used') }}: {{ number_format((float) data_get($leaveBalances, 'sick.used', 0), 2) }} / {{ number_format((float) data_get($leaveBalances, 'sick.allowed', 0), 2) }}</p>
        </div>

        <div class="rounded-2xl border border-sky-900/60 bg-sky-950/30 p-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-sky-400">{{ __('Vacation') }}</p>
            <p class="mt-1 text-lg font-semibold text-sky-100">{{ number_format((float) data_get($leaveBalances, 'vacation.remaining', 0), 2) }} <span class="text-sm font-normal text-sky-300/70">{{ __('hrs') }}</span></p>
            <p class="mt-0.5 text-[11px] text-sky-300/60">{{ __('Used') }}: {{ number_format((float) data_get($leaveBalances, 'vacation.used', 0), 2) }} / {{ number_format((float) data_get($leaveBalances, 'vacation.allowed', 0), 2) }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <form id="mobile-timecard-form" wire:submit="save" class="flex flex-col gap-5">
        {{-- Week Starting --}}
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Week Starting') }}</label>
            <input
                type="date"
                wire:model="week_starting"
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
            />
            @error('week_starting')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Notes --}}
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Notes') }}</label>
            <textarea
                wire:model="notes"
                rows="3"
                placeholder="{{ __('Optional notes for this timecard…') }}"
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 placeholder-zinc-600 focus:border-zinc-500 focus:outline-none"
            ></textarea>
            @error('notes')
                <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Entries --}}
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Daily Entries') }}</p>
                @error('entries')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            @foreach ($entries as $index => $entry)
                @if (! ($entry['delete'] ?? false))
                    <div
                        wire:key="mobile-entry-form-row-{{ $entry['row_key'] ?? $index }}"
                        class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-xs font-semibold text-zinc-400">{{ __('Entry :n', ['n' => $index + 1]) }}</p>
                            <button
                                type="button"
                                wire:click="removeEntry({{ $index }})"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-800/60 bg-rose-900/30 text-rose-300"
                                data-mobile-haptic
                                aria-label="{{ __('Remove entry') }}"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            {{-- Day --}}
                            <div class="col-span-2">
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Day') }}</label>
                                <select
                                    wire:model="entries.{{ $index }}.day_of_week"
                                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                                >
                                    <option value="0">{{ __('Sunday') }}</option>
                                    <option value="1">{{ __('Monday') }}</option>
                                    <option value="2">{{ __('Tuesday') }}</option>
                                    <option value="3">{{ __('Wednesday') }}</option>
                                    <option value="4">{{ __('Thursday') }}</option>
                                    <option value="5">{{ __('Friday') }}</option>
                                    <option value="6">{{ __('Saturday') }}</option>
                                </select>
                                @error('entries.'.$index.'.day_of_week')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Start Time --}}
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Start') }}</label>
                                <input
                                    type="time"
                                    wire:model="entries.{{ $index }}.start_time"
                                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                                />
                                @error('entries.'.$index.'.start_time')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Hours --}}
                            <div>
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Hours') }}</label>
                                <input
                                    type="number"
                                    step="0.25"
                                    min="0"
                                    max="24"
                                    wire:model="entries.{{ $index }}.hours"
                                    inputmode="decimal"
                                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                                />
                                @error('entries.'.$index.'.hours')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror

                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach (['4.00', '6.00', '8.00', '10.00', '12.00'] as $presetHours)
                                        <button
                                            type="button"
                                            wire:click="applyHoursPreset({{ $index }}, '{{ $presetHours }}')"
                                            class="rounded-full border border-zinc-700 px-2.5 py-1 text-[11px] font-semibold text-zinc-300"
                                            data-mobile-haptic
                                        >
                                            {{ $presetHours }}h
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Project --}}
                            <div class="col-span-2">
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Project') }}</label>
                                <select
                                    wire:model="entries.{{ $index }}.project_id"
                                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                                >
                                    <option value="">{{ __('Custom / Unassigned') }}</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}{{ $project->leave_category ? ' ('.str($project->leave_category)->headline().' Leave)' : '' }}</option>
                                    @endforeach
                                </select>
                                @error('entries.'.$index.'.project_id')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Cost Code --}}
                            @php
                                $projectId = $entry['project_id'] ?? null;
                                $costCodes = $projectId ? ($costCodesByProject[$projectId] ?? collect()) : collect();
                            @endphp

                            @if ($costCodes->isNotEmpty())
                                <div class="col-span-2">
                                    <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Cost Code') }}</label>
                                    <select
                                        wire:model="entries.{{ $index }}.cost_code_id"
                                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                                    >
                                        <option value="">{{ __('No Cost Code') }}</option>
                                        @foreach ($costCodes as $costCode)
                                            <option value="{{ $costCode->id }}">{{ $costCode->code }} — {{ $costCode->description }}</option>
                                        @endforeach
                                    </select>
                                    @error('entries.'.$index.'.cost_code_id')
                                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            {{-- Custom Project Name --}}
                            <div class="col-span-2">
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Custom Project Name') }}</label>
                                <input
                                    type="text"
                                    wire:model="entries.{{ $index }}.custom_project_name"
                                    placeholder="{{ __('Optional') }}"
                                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 placeholder-zinc-600 focus:border-zinc-500 focus:outline-none"
                                />
                                @error('entries.'.$index.'.custom_project_name')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Entry Notes --}}
                            <div class="col-span-2">
                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Entry Notes') }}</label>
                                <input
                                    type="text"
                                    wire:model="entries.{{ $index }}.notes"
                                    placeholder="{{ __('Optional') }}"
                                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 placeholder-zinc-600 focus:border-zinc-500 focus:outline-none"
                                />
                                @error('entries.'.$index.'.notes')
                                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Add Entry Buttons --}}
            <div class="flex flex-col gap-2">
                <button
                    type="button"
                    wire:click="addEntry"
                    data-mobile-haptic
                    class="flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl border border-zinc-700 bg-zinc-900 text-sm font-semibold text-zinc-300"
                >
                    <svg class="h-4 w-4 text-zinc-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                    {{ __('Add Entry') }}
                </button>

                @if (data_get($leaveProjectsByCategory, 'sick.id'))
                    <button
                        type="button"
                        wire:click="addLeaveEntry('sick')"
                        data-mobile-haptic
                        class="flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl border border-emerald-800/60 bg-emerald-950/30 text-sm font-semibold text-emerald-300"
                    >
                        {{ __('Add Sick Entry') }}
                    </button>
                @endif

                @if (data_get($leaveProjectsByCategory, 'vacation.id'))
                    <button
                        type="button"
                        wire:click="addLeaveEntry('vacation')"
                        data-mobile-haptic
                        class="flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl border border-sky-800/60 bg-sky-950/30 text-sm font-semibold text-sky-300"
                    >
                        {{ __('Add Vacation Entry') }}
                    </button>
                @endif
            </div>
        </div>

        <a
            href="{{ route('timecards.mobile.index') }}"
            wire:navigate
            class="flex min-h-12 items-center justify-center rounded-2xl border border-zinc-700 text-sm font-semibold text-zinc-400"
            data-mobile-haptic
        >
            {{ __('Cancel') }}
        </a>
    </form>
</div>
