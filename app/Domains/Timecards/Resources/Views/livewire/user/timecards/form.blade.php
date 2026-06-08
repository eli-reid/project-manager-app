<x-slot:domainNavbar>
    <div class="flex items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="window.Livewire.dispatch('timecard-form:add-entry')" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('Add Entry') }}</button>

            @if (data_get($leaveProjectsByCategory, 'sick.id'))
                <button type="button" onclick="window.Livewire.dispatch('timecard-form:add-sick-entry')" class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-200 dark:hover:bg-emerald-900/30">{{ __('Add Sick Entry') }}</button>
            @endif

            @if (data_get($leaveProjectsByCategory, 'vacation.id'))
                <button type="button" onclick="window.Livewire.dispatch('timecard-form:add-vacation-entry')" class="rounded-md border border-sky-300 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50 dark:border-sky-700 dark:text-sky-200 dark:hover:bg-sky-900/30">{{ __('Add Vacation Entry') }}</button>
            @endif
        </div>

        <a href="{{ route('timecards.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('Back to Timecards') }}</a>
    </div>
</x-slot:domainNavbar>

<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div>
        <flux:heading size="xl" level="1">{{ $isEdit ? __('Edit Timecard') : __('Create Timecard') }}</flux:heading>
        <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Create or update a draft timecard and manage the daily entries for the selected week.') }}
        </flux:text>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Sick Remaining') }}</p>
            <p class="mt-2 text-xl font-semibold text-emerald-900 dark:text-emerald-100">{{ number_format((float) data_get($leaveBalances, 'sick.remaining', 0), 2) }} {{ __('hrs') }}</p>
            <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/80">{{ __('Used: :used / :allowed', ['used' => number_format((float) data_get($leaveBalances, 'sick.used', 0), 2), 'allowed' => number_format((float) data_get($leaveBalances, 'sick.allowed', 0), 2)]) }}</p>
        </div>

        <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/60 dark:bg-sky-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">{{ __('Vacation Remaining') }}</p>
            <p class="mt-2 text-xl font-semibold text-sky-900 dark:text-sky-100">{{ number_format((float) data_get($leaveBalances, 'vacation.remaining', 0), 2) }} {{ __('hrs') }}</p>
            <p class="mt-1 text-xs text-sky-700/80 dark:text-sky-300/80">{{ __('Used: :used / :allowed', ['used' => number_format((float) data_get($leaveBalances, 'vacation.used', 0), 2), 'allowed' => number_format((float) data_get($leaveBalances, 'vacation.allowed', 0), 2)]) }}</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Week Starting') }}</label>
                <input type="date" wire:model="week_starting" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('week_starting') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</label>
                <textarea wire:model="notes" rows="4" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="space-y-4 rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-950/30">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Entries') }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Add one or more daily entries for this week.') }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if (data_get($leaveProjectsByCategory, 'sick.id'))
                        <button type="button" wire:click="addLeaveEntry('sick')" class="rounded-md border border-emerald-300 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-200 dark:hover:bg-emerald-900/30">{{ __('Add Sick Entry') }}</button>
                    @endif

                    @if (data_get($leaveProjectsByCategory, 'vacation.id'))
                        <button type="button" wire:click="addLeaveEntry('vacation')" class="rounded-md border border-sky-300 px-3 py-2 text-sm font-semibold text-sky-700 hover:bg-sky-50 dark:border-sky-700 dark:text-sky-200 dark:hover:bg-sky-900/30">{{ __('Add Vacation Entry') }}</button>
                    @endif

                    <button type="button" wire:click="addEntry" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ __('Add Entry') }}</button>
                </div>
            </div>

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
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ([['value' => '06:00', 'label' => '6:00 AM'], ['value' => '06:30', 'label' => '6:30 AM'], ['value' => '07:00', 'label' => '7:00 AM'], ['value' => '07:30', 'label' => '7:30 AM'], ['value' => '08:00', 'label' => '8:00 AM']] as $presetStart)
                                                <button type="button" wire:click="applyStartTimePreset({{ $index }}, '{{ $presetStart['value'] }}')" class="rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ $presetStart['label'] }}</button>
                                            @endforeach
                                        </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Hours') }}</label>
                                    <input type="number" step="0.25" min="0" max="24" wire:model="entries.{{ $index }}.hours" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                    @error('entries.'.$index.'.hours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach (['4.00', '6.00', '8.00', '10.00', '12.00'] as $presetHours)
                                            <button type="button" wire:click="applyHoursPreset({{ $index }}, '{{ $presetHours }}')" class="rounded-full border border-zinc-300 px-2.5 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ $presetHours }}h</button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Project') }}</label>
                                    <select wire:model="entries.{{ $index }}.project_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <option value="">{{ __('Custom / Unassigned') }}</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->name }}{{ $project->leave_category ? ' ('.str($project->leave_category)->headline().' Leave)' : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('entries.'.$index.'.project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="lg:col-span-2">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Cost Code') }}</label>
                                    <select wire:model="entries.{{ $index }}.cost_code_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                                        <option value="">{{ __('No Cost Code') }}</option>
                                        @php
                                            $projectId = $entry['project_id'] ?? null;
                                            $costCodes = $projectId ? ($costCodesByProject[$projectId] ?? collect()) : collect();
                                        @endphp
                                        @foreach ($costCodes as $costCode)
                                            <option value="{{ $costCode->id }}">{{ $costCode->code }} - {{ $costCode->description }}</option>
                                        @endforeach
                                    </select>
                                    @error('entries.'.$index.'.cost_code_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="flex items-end justify-end">
                                    <button type="button" wire:click="removeEntry({{ $index }})" class="rounded-md border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/20"><flux:icon.trash/></button>
                                </div>

                                <div class="lg:col-span-3">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Custom Project Name') }}</label>
                                    <input type="text" wire:model="entries.{{ $index }}.custom_project_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                    @error('entries.'.$index.'.custom_project_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="lg:col-span-3">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Entry Notes') }}</label>
                                    <input type="text" wire:model="entries.{{ $index }}.notes" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                                    @error('entries.'.$index.'.notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <a href="{{ route('timecards.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800" wire:navigate>{{ __('Cancel') }}</a>
            <button type="submit" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">{{ $isEdit ? __('Update Timecard') : __('Create Timecard') }}</button>
        </div>
    </form>
</div>