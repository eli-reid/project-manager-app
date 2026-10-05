@php
    $isEdit = $entry !== null;
    $chipBase = 'flex min-h-11 items-center justify-center rounded-xl border text-sm font-semibold transition-colors';
    $chipOn = 'border-zinc-100 bg-zinc-100 text-zinc-900';
    $chipOff = 'border-zinc-700 bg-zinc-950 text-zinc-300 active:bg-zinc-800';
    $label = 'mb-2 block text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500';
    $input = 'w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-base text-zinc-100 placeholder-zinc-600 focus:border-zinc-400 focus:outline-none';
@endphp

<div
    class="px-4 py-5 pb-48"
    x-data="{
        chip(active) { return active ? @js($chipOn) : @js($chipOff) },
        bumpHours(delta) {
            const next = Math.min(24, Math.max(0, (parseFloat($wire.hours) || 0) + delta));
            $wire.hours = next.toFixed(2);
        },
    }"
>
    <form id="mobile-entry-form" wire:submit="save" class="flex flex-col gap-6">
        <p class="text-xs text-zinc-500">
            {{ __('Week of :start – :end', ['start' => $timecard->week_starting->format('M j'), 'end' => $timecard->week_ending->format('M j, Y')]) }}
        </p>

        @if ($savedMessage)
            <div wire:key="saved-{{ md5($savedMessage) }}" class="flex items-center gap-2 rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                {{ $savedMessage }}
            </div>
        @endif

        @foreach (['timecard', 'entry'] as $errorKey)
            @error($errorKey)
                <div class="rounded-2xl border border-red-800/60 bg-red-950/40 px-4 py-3 text-sm font-medium text-red-200">{{ $message }}</div>
            @enderror
        @endforeach

        {{-- Day --}}
        <section>
            <span class="{{ $label }}">{{ __('Day') }}</span>
            <div class="grid grid-cols-7 gap-1.5">
                @foreach ($weekDays as $weekDay)
                    @php
                        $dateValue = $weekDay->toDateString();
                        $loggedHours = (float) ($this->dayTotals[$dateValue] ?? 0);
                    @endphp
                    <button
                        type="button"
                        wire:key="day-{{ $dateValue }}"
                        x-on:click="$wire.date = '{{ $dateValue }}'"
                        class="flex min-h-16 flex-col items-center justify-center rounded-xl border px-0.5 py-1.5 transition-colors"
                        :class="chip($wire.date === '{{ $dateValue }}')"
                        aria-label="{{ $weekDay->format('l, M j') }}"
                        data-mobile-haptic
                    >
                        <span class="text-[10px] font-semibold uppercase tracking-wide opacity-70">{{ $weekDay->format('D') }}</span>
                        <span class="text-base font-semibold leading-tight">{{ $weekDay->format('j') }}</span>
                        <span class="text-[10px] tabular-nums opacity-70">{{ $loggedHours > 0 ? rtrim(rtrim(number_format($loggedHours, 2), '0'), '.').'h' : '–' }}</span>
                    </button>
                @endforeach
            </div>
            @error('date') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </section>

        {{-- Type --}}
        @if ($this->leaveProjects->isNotEmpty())
            <section>
                <span class="{{ $label }}">{{ __('Type') }}</span>
                <div @class([
                    'grid gap-1.5 rounded-2xl border border-zinc-800 bg-zinc-900 p-1',
                    'grid-cols-2' => $this->leaveProjects->count() === 1,
                    'grid-cols-3' => $this->leaveProjects->count() >= 2,
                ])>
                    @foreach (['work' => __('Work'), 'sick' => __('Sick'), 'vacation' => __('Vacation')] as $typeKey => $typeLabel)
                        @if ($typeKey === 'work' || $this->leaveProjects->has($typeKey))
                            <button
                                type="button"
                                wire:key="type-{{ $typeKey }}"
                                wire:click="setEntryType('{{ $typeKey }}')"
                                @class([
                                    'min-h-11 rounded-xl text-sm font-semibold transition-colors',
                                    'bg-zinc-100 text-zinc-900' => $entryType === $typeKey,
                                    'text-zinc-400 active:bg-zinc-800' => $entryType !== $typeKey,
                                ])
                                data-mobile-haptic
                            >
                                {{ $typeLabel }}
                            </button>
                        @endif
                    @endforeach
                </div>

                @if ($entryType !== 'work')
                    <p @class([
                        'mt-2 text-xs',
                        'text-emerald-300/80' => $entryType === 'sick',
                        'text-sky-300/80' => $entryType === 'vacation',
                    ])>
                        {{ __(':hours hrs :type remaining (:used of :allowed used)', [
                            'hours' => number_format((float) data_get($leaveBalances, $entryType.'.remaining', 0), 2),
                            'type' => $entryType,
                            'used' => number_format((float) data_get($leaveBalances, $entryType.'.used', 0), 2),
                            'allowed' => number_format((float) data_get($leaveBalances, $entryType.'.allowed', 0), 2),
                        ]) }}
                    </p>
                @endif
                @error('project_id') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
            </section>
        @endif

        {{-- Project --}}
        @if ($entryType === 'work')
            <section class="flex flex-col gap-3">
                <div>
                    <label for="entry-project" class="{{ $label }}">{{ __('Project') }}</label>
                    <select id="entry-project" wire:model.live="project_id" class="{{ $input }}">
                        <option value="">{{ __('Custom / Unassigned') }}</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                    @if ($this->leaveProjects->isEmpty())
                        @error('project_id') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    @endif
                </div>

                @if (blank($project_id))
                    <div>
                        <label for="entry-custom-project" class="{{ $label }}">{{ __('Custom Project Name') }}</label>
                        <input
                            id="entry-custom-project"
                            type="text"
                            wire:model="custom_project_name"
                            placeholder="{{ __('e.g. Shop time, Warehouse') }}"
                            class="{{ $input }}"
                        />
                        @error('custom_project_name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if ($costCodes->isNotEmpty())
                    <div>
                        <label for="entry-cost-code" class="{{ $label }}">{{ __('Cost Code') }}</label>
                        <select id="entry-cost-code" wire:model="cost_code_id" class="{{ $input }}">
                            <option value="">{{ __('No Cost Code') }}</option>
                            @foreach ($costCodes as $costCode)
                                <option value="{{ $costCode->id }}">{{ $costCode->code }} — {{ $costCode->description }}</option>
                            @endforeach
                        </select>
                        @error('cost_code_id') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                @endif
            </section>
        @endif

        {{-- Hours --}}
        <section>
            <label for="entry-hours" class="{{ $label }}">{{ __('Hours') }}</label>
            <div class="flex items-stretch gap-2">
                <button
                    type="button"
                    x-on:click="bumpHours(-0.5)"
                    class="inline-flex min-h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 text-xl font-semibold text-zinc-200 active:bg-zinc-800"
                    aria-label="{{ __('Decrease hours') }}"
                    data-mobile-haptic
                >&minus;</button>
                <input
                    id="entry-hours"
                    type="number"
                    step="0.25"
                    min="0"
                    max="24"
                    inputmode="decimal"
                    placeholder="0.00"
                    wire:model="hours"
                    class="min-h-14 w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 text-center text-2xl font-semibold tabular-nums text-zinc-50 placeholder-zinc-700 focus:border-zinc-400 focus:outline-none"
                />
                <button
                    type="button"
                    x-on:click="bumpHours(0.5)"
                    class="inline-flex min-h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-950 text-xl font-semibold text-zinc-200 active:bg-zinc-800"
                    aria-label="{{ __('Increase hours') }}"
                    data-mobile-haptic
                >+</button>
            </div>
            <div class="mt-2 grid grid-cols-4 gap-1.5">
                @foreach (\App\Domains\Timecards\Livewire\Mobile\Timecards\EntryForm::HOUR_PRESETS as $presetHours)
                    <button
                        type="button"
                        wire:key="hours-{{ $presetHours }}"
                        x-on:click="$wire.hours = '{{ $presetHours }}'"
                        class="{{ $chipBase }}"
                        :class="chip(parseFloat($wire.hours) === {{ (float) $presetHours }})"
                        data-mobile-haptic
                    >
                        {{ (int) $presetHours }}h
                    </button>
                @endforeach
            </div>
            @error('hours') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </section>

        {{-- Start time --}}
        <section>
            <label for="entry-start-time" class="{{ $label }}">{{ __('Start Time') }} <span class="normal-case tracking-normal text-zinc-600">({{ __('optional') }})</span></label>
            <div class="grid grid-cols-4 gap-1.5">
                @foreach (\App\Domains\Timecards\Livewire\Mobile\Timecards\EntryForm::START_TIME_PRESETS as $presetStart)
                    <button
                        type="button"
                        wire:key="start-{{ $presetStart }}"
                        x-on:click="$wire.start_time = '{{ $presetStart }}'"
                        class="{{ $chipBase }}"
                        :class="chip($wire.start_time === '{{ $presetStart }}')"
                        data-mobile-haptic
                    >
                        {{ \Illuminate\Support\Carbon::createFromFormat('H:i', $presetStart)->format('g:i') }}
                    </button>
                @endforeach
            </div>
            <input
                id="entry-start-time"
                type="time"
                wire:model="start_time"
                class="mt-2 {{ $input }}"
            />
            @error('start_time') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </section>

        {{-- Notes --}}
        <section>
            <label for="entry-notes" class="{{ $label }}">{{ __('Notes') }} <span class="normal-case tracking-normal text-zinc-600">({{ __('optional') }})</span></label>
            <textarea
                id="entry-notes"
                rows="2"
                wire:model="notes"
                placeholder="{{ __('What did you work on?') }}"
                class="{{ $input }}"
            ></textarea>
            @error('notes') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </section>

        @if ($isEdit)
            <button
                type="button"
                wire:click="delete"
                wire:confirm="{{ __('Delete this entry?') }}"
                class="flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-rose-900/60 text-sm font-semibold text-rose-300 active:bg-rose-950/40"
                data-mobile-haptic
            >
                {{ __('Delete Entry') }}
            </button>
        @endif
    </form>

    {{-- Sticky actions --}}
    <div class="pointer-events-none fixed inset-x-0 bottom-20 z-40">
        <div class="mx-auto flex max-w-md gap-2 px-4">
            @unless ($isEdit)
                <button
                    type="button"
                    wire:click="saveAndAddAnother"
                    wire:loading.attr="disabled"
                    class="pointer-events-auto flex min-h-14 flex-1 items-center justify-center rounded-2xl border border-zinc-600 bg-zinc-900/95 px-3 text-sm font-semibold text-zinc-100 shadow-lg backdrop-blur active:bg-zinc-800 disabled:opacity-60"
                    data-mobile-haptic
                >
                    <span wire:loading.remove wire:target="saveAndAddAnother">{{ __('Save & Add Another') }}</span>
                    <span wire:loading wire:target="saveAndAddAnother">{{ __('Saving…') }}</span>
                </button>
            @endunless

            <button
                type="submit"
                form="mobile-entry-form"
                wire:loading.attr="disabled"
                class="pointer-events-auto flex min-h-14 flex-1 items-center justify-center rounded-2xl bg-zinc-100 px-3 text-base font-semibold text-zinc-900 shadow-lg active:bg-zinc-300 disabled:opacity-60"
                data-mobile-haptic
            >
                <span wire:loading.remove wire:target="save">{{ $isEdit ? __('Save Changes') : __('Save Entry') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </button>
        </div>
    </div>
</div>
