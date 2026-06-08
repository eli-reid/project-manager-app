<x-slot:domainNavbar>
    <div class="flex items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" form="timecard-form-desktop" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">{{ __('Save') }}</button>

            <button type="button" onclick="window.Livewire.dispatch('timecard-form:add-entry')" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('Add Entry') }}</button>

            @if (data_get($leaveProjectsByCategory, 'sick.id'))
                <button type="button" onclick="window.Livewire.dispatch('timecard-form:add-sick-entry')" class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-200 dark:hover:bg-emerald-900/30">{{ __('Add Sick Entry') }}</button>
            @endif

            @if (data_get($leaveProjectsByCategory, 'vacation.id'))
                <button type="button" onclick="window.Livewire.dispatch('timecard-form:add-vacation-entry')" class="rounded-md border border-sky-300 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50 dark:border-sky-700 dark:text-sky-200 dark:hover:bg-sky-900/30">{{ __('Add Vacation Entry') }}</button>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <div class="rounded-md border border-zinc-300 bg-zinc-50/70 px-3 py-2 text-xs font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900/30 dark:text-zinc-200">
                {{ __('Week Starting') }}: {{ \Illuminate\Support\Carbon::parse($week_starting)->format('M j, Y') }}
            </div>

            <div class="rounded-md border border-emerald-300 bg-emerald-50/70 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-200">
                {{ __('Sick Remaining') }}: {{ number_format((float) data_get($leaveBalances, 'sick.remaining', 0), 2) }} {{ __('hrs') }}
                · {{ __('Used') }}: {{ number_format((float) data_get($leaveBalances, 'sick.used', 0), 2) }} {{ __('hrs') }}
            </div>

            <div class="rounded-md border border-sky-300 bg-sky-50/70 px-3 py-2 text-xs font-semibold text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-200">
                {{ __('Vacation Remaining') }}: {{ number_format((float) data_get($leaveBalances, 'vacation.remaining', 0), 2) }} {{ __('hrs') }}
                · {{ __('Used') }}: {{ number_format((float) data_get($leaveBalances, 'vacation.used', 0), 2) }} {{ __('hrs') }}
            </div>
        </div>
    </div>
</x-slot:domainNavbar>

<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <form id="timecard-form-desktop" wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        @error('week_starting') <p class="text-xs text-red-600">{{ $message }}</p> @enderror


            @error('entries') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="space-y-4">
                @foreach ($entries as $index => $entry)
                    @if (! ($entry['delete'] ?? false))
                        <div wire:key="timecard-entry-form-row-{{ $entry['row_key'] ?? $index }}" class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="grid gap-4 lg:grid-cols-6">
                                <div>
                                       <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Day of Week') }}</label>
                                       <select wire:model="entries.{{ $index }}.day_of_week" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                           <option value="0">{{ __('Sunday') }}</option>
                                           <option value="1">{{ __('Monday') }}</option>
                                           <option value="2">{{ __('Tuesday') }}</option>
                                           <option value="3">{{ __('Wednesday') }}</option>
                                           <option value="4">{{ __('Thursday') }}</option>
                                           <option value="5">{{ __('Friday') }}</option>
                                           <option value="6">{{ __('Saturday') }}</option>
                                       </select>
                                       @error('entries.'.$index.'.day_of_week') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Start') }}</label>
                                    <input type="time" wire:model="entries.{{ $index }}.start_time" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                    @error('entries.'.$index.'.start_time') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    <div class="mt-2 grid max-w-xs grid-cols-2 gap-2">
                                        @foreach ([['value' => '06:00', 'label' => '6:00A'], ['value' => '06:30', 'label' => '6:30A'], ['value' => '07:00', 'label' => '7:00A'], ['value' => '07:30', 'label' => '7:30A']] as $presetStart)
                                            <button type="button" wire:click="applyStartTimePreset({{ $index }}, '{{ $presetStart['value'] }}')" class="rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ $presetStart['label'] }}</button>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Hours') }}</label>
                                    <input type="number" step="0.25" min="0" max="24" wire:model="entries.{{ $index }}.hours" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                    @error('entries.'.$index.'.hours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach (['4.00', '6.00', '8.00', '10.00'] as $presetHours)
                                            <button type="button" wire:click="applyHoursPreset({{ $index }}, '{{ $presetHours }}')" class="rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ $presetHours }}h</button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="space-y-4 lg:col-span-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Project') }}</label>
                                        <select wire:model="entries.{{ $index }}.project_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                            <option value="">{{ __('Custom / Unassigned') }}</option>
                                            @foreach ($projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->name }}{{ $project->leave_category ? ' ('.str($project->leave_category)->headline().' Leave)' : '' }}</option>
                                            @endforeach
                                        </select>
                                        @error('entries.'.$index.'.project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Custom Project Name') }}</label>
                                        <input type="text" wire:model="entries.{{ $index }}.custom_project_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                        @error('entries.'.$index.'.custom_project_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="flex items-start justify-end lg:col-start-6 lg:row-start-1">
                                    <button type="button" wire:click="removeEntry({{ $index }})" class="rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/20"><flux:icon.trash/></button>
                                </div>

                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <a href="{{ route('timecards.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:navigate>{{ __('Cancel') }}</a>
        </div>
    </form>
</div>