<div class="mx-auto w-full max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isEdit ? 'Edit Pay Rate' : 'Create Pay Rate' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Set typed pay rates for employee profiles with optional project-specific scope.</p>
        </div>

        <a href="{{ route('admin.payroll.rates.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
            Back
        </a>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee Profile</label>
                <select wire:model="payroll_employee_profile_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Select an employee profile</option>
                    @foreach ($profiles as $profile)
                        @php
                            $fullName = trim((string) ($profile->user?->first_name ?? '').' '.(string) ($profile->user?->last_name ?? ''));
                        @endphp
                        <option value="{{ $profile->id }}">{{ ($profile->employee_number ? $profile->employee_number.' - ' : '').($fullName !== '' ? $fullName : 'Unknown Employee') }}</option>
                    @endforeach
                </select>
                @error('payroll_employee_profile_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Rate Type</label>
                <select wire:model="pay_rate_type_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Select a rate type</option>
                    @foreach ($rateTypes as $rateType)
                        <option value="{{ $rateType->id }}">{{ $rateType->name }} ({{ $rateType->key }})</option>
                    @endforeach
                </select>
                @error('pay_rate_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project Scope</label>
                <select wire:model="project_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Default (All Projects)</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}{{ $project->project_number ? ' ('.$project->project_number.')' : '' }}</option>
                    @endforeach
                </select>
                @error('project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Rate Amount</label>
                <input type="number" step="0.0001" min="0" wire:model="rate_amount" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('rate_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Effective Date</label>
                <input type="date" wire:model="effective_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('effective_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Expiration Date</label>
                <input type="date" wire:model="expiration_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('expiration_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <a href="{{ route('admin.payroll.rates.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
            <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-zinc-900 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">{{ $isEdit ? 'Update Rate' : 'Create Rate' }}</button>
        </div>
    </form>
</div>
