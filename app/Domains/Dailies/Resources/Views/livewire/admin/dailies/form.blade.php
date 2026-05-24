<div class="w-full space-y-6">
    <div>
        <flux:heading size="xl" level="1">{{ $isEdit ? __('Edit Daily Report') : __('Create Daily Report') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Admins can create or edit daily reports on behalf of any user.') }}</flux:text>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        {{-- Worker selector (admin only) --}}
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Worker') }}</label>
            <select wire:model="user_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">{{ __('Select a worker...') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ trim($user->first_name.' '.$user->last_name) }}</option>
                @endforeach
            </select>
            @error('user_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Report Date') }}</label>
                <input type="date" wire:model="report_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('report_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Project') }}</label>
                <select wire:model="project_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">{{ __('Custom Project') }}</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
                @error('project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Custom Project Name') }}</label>
                <input type="text" wire:model="custom_project_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Required when no project is selected.') }}</p>
                @error('custom_project_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Regular Hours') }}</label>
                <input type="number" min="0" max="24" step="0.25" wire:model="total_regular_hours" readonly class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Calculated from work performed items.') }}</p>
                @error('total_regular_hours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Overtime Hours') }}</label>
                <input type="number" min="0" max="24" step="0.25" wire:model="total_overtime_hours" readonly class="w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Calculated from work performed items marked overtime.') }}</p>
                @error('total_overtime_hours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-zinc-50/60 p-3 dark:border-zinc-700 dark:bg-zinc-950/30">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Weather (Auto)') }}</p>
            <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">
                {{ $weather_condition ?: __('Will auto-populate from project/default address on save.') }}
                @if ($temperature !== null)
                    ({{ $temperature }} {{ $temperature_unit }})
                @endif
            </p>
            @if (filled($weather_source_location))
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Source') }}: {{ $weather_source_location }}</p>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="md:col-span-3">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Additional Notes') }}</label>
                <textarea rows="4" wire:model="additional_notes" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                @error('additional_notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Work Performed With Hours --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Work Performed') }}</label>
                <button type="button" wire:click="addWorkPerformedItem" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                    + {{ __('Add Item') }}
                </button>
            </div>
            @forelse ($work_performed as $index => $item)
                <div wire:key="wp-{{ $index }}" class="mb-2 space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="grid gap-2 md:grid-cols-[1fr_140px_44px]">
                    <input type="text" wire:model="work_performed.{{ $index }}.description" placeholder="{{ __('Describe work completed...') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    <input type="number" min="0" max="24" step="0.25" wire:model="work_performed.{{ $index }}.hours" placeholder="{{ __('Hours') }}" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    <button type="button" wire:click="removeWorkPerformedItem({{ $index }})" class="shrink-0 rounded p-1 text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400" title="{{ __('Remove') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Employees') }}</label>
                        <select multiple wire:model="work_performed.{{ $index }}.employees" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                            @foreach ($onsiteEmployeeOptions as $employeeName)
                                <option value="{{ $employeeName }}">{{ $employeeName }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Select all employees who worked on this item.') }}</p>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model="work_performed.{{ $index }}.is_overtime" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950" />
                        <span>{{ __('Overtime') }}</span>
                    </label>
                </div>
                @error("work_performed.{$index}.description") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error("work_performed.{$index}.hours") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error("work_performed.{$index}.employees") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error("work_performed.{$index}.employees.*") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error("work_performed.{$index}.is_overtime") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @empty
                <p class="text-xs italic text-zinc-400">{{ __('No items added.') }}</p>
            @endforelse
        </div>

        {{-- Employees Onsite --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Employees Onsite') }}</label>
            </div>

            <div class="grid gap-2 rounded-lg border border-zinc-200 p-3 md:grid-cols-2 dark:border-zinc-700">
                @forelse ($onsiteEmployeeOptions as $employeeName)
                    <label wire:key="admin-onsite-employee-{{ md5($employeeName) }}" class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model="onsite_employees" value="{{ $employeeName }}" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950" />
                        <span>{{ $employeeName }}</span>
                    </label>
                @empty
                    <p class="text-xs italic text-zinc-400">{{ __('No active employees found.') }}</p>
                @endforelse
            </div>
            @error('onsite_employees') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('onsite_employees.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Materials Used --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Materials Used') }}</label>
                <button type="button" wire:click="addItem('materials_used')" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                    + {{ __('Add Item') }}
                </button>
            </div>
            @forelse ($materials_used as $index => $item)
                <div wire:key="mu-{{ $index }}" class="mb-2 flex items-center gap-2">
                    <input type="text" wire:model="materials_used.{{ $index }}" placeholder="{{ __('e.g. 50 lbs concrete, 10 2x4 boards...') }}" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    <button type="button" wire:click="removeItem('materials_used', {{ $index }})" class="shrink-0 rounded p-1 text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400" title="{{ __('Remove') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @error("materials_used.{$index}") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @empty
                <p class="text-xs italic text-zinc-400">{{ __('No items added.') }}</p>
            @endforelse
        </div>

        {{-- Equipment Used --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Equipment Used') }}</label>
                <button type="button" wire:click="addItem('equipment_used')" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                    + {{ __('Add Item') }}
                </button>
            </div>
            @forelse ($equipment_used as $index => $item)
                <div wire:key="eu-{{ $index }}" class="mb-2 flex items-center gap-2">
                    <input type="text" wire:model="equipment_used.{{ $index }}" placeholder="{{ __('e.g. Excavator, Forklift...') }}" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    <button type="button" wire:click="removeItem('equipment_used', {{ $index }})" class="shrink-0 rounded p-1 text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400" title="{{ __('Remove') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @error("equipment_used.{$index}") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @empty
                <p class="text-xs italic text-zinc-400">{{ __('No items added.') }}</p>
            @endforelse
        </div>

        {{-- Safety Issues --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Safety Issues') }}</label>
                <button type="button" wire:click="addItem('safety_issues')" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                    + {{ __('Add Item') }}
                </button>
            </div>
            @forelse ($safety_issues as $index => $item)
                <div wire:key="si-{{ $index }}" class="mb-2 flex items-center gap-2">
                    <input type="text" wire:model="safety_issues.{{ $index }}" placeholder="{{ __('Describe any safety concern or incident...') }}" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    <button type="button" wire:click="removeItem('safety_issues', {{ $index }})" class="shrink-0 rounded p-1 text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400" title="{{ __('Remove') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @error("safety_issues.{$index}") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @empty
                <p class="text-xs italic text-zinc-400">{{ __('None reported.') }}</p>
            @endforelse
        </div>

        {{-- Delays --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Delays') }}</label>
                <button type="button" wire:click="addItem('delays')" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                    + {{ __('Add Item') }}
                </button>
            </div>
            @forelse ($delays as $index => $item)
                <div wire:key="dl-{{ $index }}" class="mb-2 flex items-center gap-2">
                    <input type="text" wire:model="delays.{{ $index }}" placeholder="{{ __('Describe any delay or cause...') }}" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    <button type="button" wire:click="removeItem('delays', {{ $index }})" class="shrink-0 rounded p-1 text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400" title="{{ __('Remove') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @error("delays.{$index}") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @empty
                <p class="text-xs italic text-zinc-400">{{ __('No delays recorded.') }}</p>
            @endforelse
        </div>

        {{-- Visitors --}}
        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Visitors') }}</label>
                <button type="button" wire:click="addItem('visitors')" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20">
                    + {{ __('Add Item') }}
                </button>
            </div>
            @forelse ($visitors as $index => $item)
                <div wire:key="vi-{{ $index }}" class="mb-2 flex items-center gap-2">
                    <input type="text" wire:model="visitors.{{ $index }}" placeholder="{{ __('Name or company...') }}" class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                    <button type="button" wire:click="removeItem('visitors', {{ $index }})" class="shrink-0 rounded p-1 text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400" title="{{ __('Remove') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                @error("visitors.{$index}") <p class="mb-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @empty
                <p class="text-xs italic text-zinc-400">{{ __('No visitors recorded.') }}</p>
            @endforelse
        </div>

        @error('dailyReport')
            <p class="text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror

        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <a
                href="{{ $isEdit ? route('admin.dailies.show', $dailyReport) : route('admin.dailies.index') }}"
                wire:navigate
                class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                {{ __('Cancel') }}
            </a>

            <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                {{ $isEdit ? __('Update Report') : __('Create Report') }}
            </button>
        </div>
    </form>
</div>
