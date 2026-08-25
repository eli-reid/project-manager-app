<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isEdit ? 'Edit User' : 'Create User' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage account details and role assignments.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">First Name</label>
                <input type="text" wire:model.live="first_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('first_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Last Name</label>
                <input type="text" wire:model.live="last_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Username</label>
                <input type="text" wire:model.live="username" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('username') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Email</label>
                <input type="email" wire:model.live="email" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Phone</label>
                <input type="tel" wire:model.live="phone" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($isEdit)
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Password (optional)</label>
                    <input type="password" wire:model.live="password" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Confirm Password</label>
                    <input type="password" wire:model.live="password_confirmation" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                </div>
            @else
                <div class="md:col-span-2">
                    <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-100">
                        <p class="font-semibold">Invitation-based setup</p>
                        <p class="mt-1 text-sky-800 dark:text-sky-200">A temporary password will be generated automatically and emailed to this user after you create the account.</p>
                    </div>
                </div>
            @endif

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" wire:model.live="is_active" class="rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" />
                    Active
                </label>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Roles</h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($roles as $role)
                    <label class="flex items-start gap-2 rounded-lg border border-zinc-200 p-3 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-300" wire:key="role-option-{{ $role->id }}">
                        <input type="checkbox" value="{{ $role->id }}" wire:model.live="selectedRoleIds" class="mt-0.5 rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" />
                        <span>
                            <span class="font-semibold">{{ $role->name }}</span>
                            @if ($role->description)
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $role->description }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
            @error('selectedRoleIds') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('selectedRoleIds.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Pay Rates</h2>

            @if (! $isEdit)
                <div class="mt-2 space-y-4">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Pay rates can be assigned after the user is saved. If this user needs payroll access, you can create the payroll profile now and finish the setup in one submit.
                    </p>

                    @if ($canCreatePayrollProfiles)
                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model.live="create_payroll_profile_on_save" class="rounded border-zinc-300 text-zinc-900 dark:border-zinc-700" />
                            Create payroll profile during user creation
                        </label>
                        @error('create_payroll_profile_on_save') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        @if ($create_payroll_profile_on_save)
                            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Payroll Profile</h3>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">These details will be saved together with the user account.</p>

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
                                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">SSN</label>
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
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">You do not have permission to create payroll profiles during user setup.</p>
                    @endif
                </div>
            @elseif (! $canManagePayrollRates)
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    You do not have permission to manage pay rates.
                </p>
            @elseif (! $payrollProfile)
                <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                    This user does not have a payroll employee profile yet, so pay rates cannot be assigned.
                </p>
                @error('pay_rates') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

                @if ($canCreatePayrollProfiles)
                    <div class="mt-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Create Payroll Profile</h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Once created, you can immediately assign pay rates below.</p>

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
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">SSN</label>
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

                        @error('payroll_profile') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

                        <div class="mt-4 flex justify-end">
                            <button type="button" wire:click="createPayrollProfile" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                                Create Payroll Profile
                            </button>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">You do not have permission to create payroll profiles.</p>
                @endif
            @else
                @if ($canUpdatePayrollProfiles)
                    <div class="mt-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Edit Payroll Profile</h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Update core employee payroll details for this user.</p>

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
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">SSN (optional to keep existing)</label>
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

                        <div class="mt-4 flex justify-end">
                            <button type="button" wire:click="updatePayrollProfile" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                                Update Payroll Profile
                            </button>
                        </div>
                    </div>
                @endif

                <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Type</th>
                                <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Project</th>
                                <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Rate</th>
                                <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Effective</th>
                                <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Expires</th>
                                <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Approved By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($payRates as $payRate)
                                <tr wire:key="pay-rate-row-{{ $payRate->id }}" class="bg-white dark:bg-zinc-900">
                                    <td class="px-3 py-2 text-zinc-800 dark:text-zinc-200">{{ $payRate->payRateType?->name ?? 'Unknown' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                        {{ $payRate->project?->name ?? 'All Projects' }}
                                        @if ($payRate->project?->project_number)
                                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $payRate->project->project_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-zinc-800 dark:text-zinc-200">${{ number_format((float) $payRate->rate_amount, 2) }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ optional($payRate->effective_date)->toDateString() }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ optional($payRate->expiration_date)->toDateString() ?? 'Active' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-300">{{ $payRate->approver?->first_name }} {{ $payRate->approver?->last_name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        No pay rates assigned yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Rate Type</label>
                        <select wire:model.live="new_pay_rate_type_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Select a rate type</option>
                            @foreach ($payRateTypes as $rateType)
                                <option value="{{ $rateType->id }}">{{ $rateType->name }}</option>
                            @endforeach
                        </select>
                        @error('new_pay_rate_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project (optional)</label>
                        <select wire:model.live="new_project_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">All projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}{{ $project->project_number ? ' ('.$project->project_number.')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('new_project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Rate Amount</label>
                        <input type="number" min="0" step="0.0001" wire:model.live="new_rate_amount" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                        @error('new_rate_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Effective Date</label>
                        <input type="date" wire:model.live="new_effective_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                        @error('new_effective_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Expiration Date (optional)</label>
                        <input type="date" wire:model.live="new_expiration_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                        @error('new_expiration_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="button" wire:click="addPayRate" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                        Add Pay Rate
                    </button>
                </div>
            @endif
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                {{ $isEdit ? 'Save User' : 'Create User' }}
            </button>
        </div>
    </form>
</div>