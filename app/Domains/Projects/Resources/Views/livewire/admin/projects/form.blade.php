<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isEdit ? 'Edit Project' : 'Create Project' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $isEdit ? 'Update project details, client, and location.' : 'Set project details, then assign client and location.' }}</p>
        </div>

        <a href="{{ route('admin.projects.index') }}" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project Name</label>
                <input type="text" wire:model="name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project #</label>
                <input type="text" wire:model="project_number" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Leave blank to auto-generate from project settings.</p>
                @error('project_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Accounting Code</label>
                <select wire:model="accounting_code_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">No accounting code</option>
                    @foreach ($accountingCodes as $accountingCode)
                        <option value="{{ $accountingCode->id }}">{{ $accountingCode->code }} - {{ $accountingCode->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Select a shared code to group bulk material and cost reporting across related projects.</p>
                @error('accounting_code_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</label>
                <textarea wire:model="description" rows="4" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</label>
                <select wire:model="status" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Budget</label>
                <input type="number" min="0" step="0.01" wire:model="budget" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" placeholder="0.00" />
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Optional total budget used in project financial reporting.</p>
                @error('budget') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center pt-8">
                <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" wire:model="is_active" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900" />
                    <span>Active</span>
                </label>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Start Date</label>
                <input type="date" wire:model="start_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('start_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">End Date</label>
                <input type="date" wire:model="end_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Client</label>
                <select wire:model.live="client_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Select a client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                    @endforeach
                </select>
                @error('client_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Address</label>
                <select wire:model="address_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Select an address</option>
                    @foreach ($addresses as $address)
                        <option value="{{ $address->id }}">{{ collect([$address->address1, $address->city, $address->state])->filter()->implode(', ') }}</option>
                    @endforeach
                </select>
                @error('address_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Leave Tracking</label>
                <select wire:model="leave_category" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Standard project</option>
                    <option value="sick">Sick leave project</option>
                    <option value="vacation">Vacation leave project</option>
                </select>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Leave projects are excluded from project labor cost reporting and counted in employee leave usage.</p>
                @error('leave_category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Default Pay Rate Type</label>
                <select wire:model="pay_rate_type_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Select a pay rate type</option>
                    @foreach ($payRateTypes as $payRateType)
                        <option value="{{ $payRateType->id }}">{{ $payRateType->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Used as the default rate type for project labor cost calculations.</p>
                @error('pay_rate_type_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <livewire:clients::admin.clients.inline-create-widget />
            <livewire:addresses::admin.addresses.inline-create-widget :client-id="$client_id" />
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <a href="{{ route('admin.projects.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
            <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">{{ $isEdit ? 'Update Project' : 'Create Project' }}</button>
        </div>
    </form>
</div>
