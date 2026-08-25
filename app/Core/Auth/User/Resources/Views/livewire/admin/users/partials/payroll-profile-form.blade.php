<div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Employee Number</label>
            <input type="text" wire:model.live="profile_employee_number" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_employee_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Job Classification</label>
            <input type="text" wire:model.live="profile_job_classification" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_job_classification') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $ssnLabel }}</label>
            <input type="password" inputmode="numeric" wire:model.live="profile_ssn" placeholder="xxx-xx-xxxx" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Format: xxx-xx-xxxx</p>
            @error('profile_ssn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date of Birth</label>
            <input type="date" wire:model.live="profile_date_of_birth" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_date_of_birth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Hire Date</label>
            <input type="date" wire:model.live="profile_hire_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_hire_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Termination Date (optional)</label>
            <input type="date" wire:model.live="profile_termination_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_termination_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</label>
            <select wire:model.live="profile_status" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="terminated">Terminated</option>
            </select>
            @error('profile_status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Pay Type</label>
            <select wire:model.live="profile_pay_type" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="hourly">Hourly</option>
                <option value="salary">Salary</option>
            </select>
            @error('profile_pay_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Department (optional)</label>
            <input type="text" wire:model.live="profile_department" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_department') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Union Code (optional)</label>
            <input type="text" wire:model.live="profile_union_code" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_union_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Sick Hours Allowance</label>
            <input type="number" min="0" step="0.25" wire:model.live="profile_sick_hours_allowance" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_sick_hours_allowance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Vacation Hours Allowance</label>
            <input type="number" min="0" step="0.25" wire:model.live="profile_vacation_hours_allowance" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
            @error('profile_vacation_hours_allowance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <input type="checkbox" wire:model.live="profile_direct_deposit_active" class="rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" />
                Direct deposit active
            </label>
            @error('profile_direct_deposit_active') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    @if (($showPayrollProfileError ?? false) === true)
        @error('payroll_profile') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    @endif

    @if (($actionMethod ?? null) !== null && ($actionLabel ?? null) !== null)
        <div class="mt-4 flex justify-end">
            <button type="button" wire:click="{{ $actionMethod }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                {{ $actionLabel }}
            </button>
        </div>
    @endif
</div>