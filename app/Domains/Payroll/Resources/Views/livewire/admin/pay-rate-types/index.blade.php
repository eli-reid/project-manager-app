<section class="w-full space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Pay Rate Types</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                Manage typed rates used for payroll resolution and compliance calculations.
            </flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('admin.payroll.rates.index') }}" wire:navigate variant="ghost">
                Employee Rates
            </flux:button>
            <flux:button wire:click="openCreate" variant="primary" icon="plus">
                New Rate Type
            </flux:button>
        </div>
    </div>

    {{-- Delete error --}}
    @error('delete')
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>Cannot delete</flux:callout.heading>
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    {{-- Filters --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.400ms="search"
                placeholder="Search name, key, or description..."
                icon="magnifying-glass"
            />
        </div>
        <flux:select wire:model.live="statusFilter" class="sm:w-44">
            <flux:select.option value="all">All Statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="inactive">Inactive</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Key</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Rates</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Scope</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($types as $type)
                        <tr>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $type->name }}</td>
                            <td class="px-4 py-3 font-mono text-sm text-zinc-700 dark:text-zinc-300">{{ $type->key }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $type->description ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">{{ $type->pay_rates_count }}</td>
                            <td class="px-4 py-3 text-sm">
                                <flux:badge :color="$type->is_active ? 'green' : 'zinc'" size="sm">
                                    {{ $type->is_active ? 'Active' : 'Inactive' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <flux:badge :color="$type->is_system ? 'blue' : 'amber'" size="sm">
                                    {{ $type->is_system ? 'System' : 'Custom' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button wire:click="openEdit('{{ $type->id }}')" variant="ghost" size="sm" icon="pencil" tooltip="Edit" />
                                    @unless ($type->is_system)
                                        <flux:button
                                            wire:click="confirmDelete('{{ $type->id }}')"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            tooltip="Delete"
                                            class="text-red-500 hover:text-red-600 dark:text-red-400"
                                        />
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No pay rate types match the current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal wire:model="showFormModal" class="w-full max-w-lg">
        <div class="space-y-4">
            <flux:heading size="lg">
                {{ $editingId ? 'Edit Pay Rate Type' : 'New Pay Rate Type' }}
            </flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model.live="formName" placeholder="e.g. Prevailing Base Rate" />
                    <flux:error name="formName" />
                </flux:field>

                <flux:field>
                    <flux:label>
                        Key
                        @if ($isSystemType)
                            <flux:badge size="sm" class="ml-1">system — locked</flux:badge>
                        @endif
                    </flux:label>
                    <flux:input
                        wire:model="formKey"
                        placeholder="e.g. prevailing_base_rate"
                        :disabled="$isSystemType"
                        class="font-mono"
                    />
                    <flux:text size="sm" class="text-zinc-400">Lowercase letters, numbers, and underscores. Auto-generated from name.</flux:text>
                    <flux:error name="formKey" />
                </flux:field>

                <flux:field>
                    <flux:label>Description <span class="font-normal text-zinc-400">(optional)</span></flux:label>
                    <flux:textarea wire:model="formDescription" rows="2" placeholder="Brief description of when this rate type applies." />
                    <flux:error name="formDescription" />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Sort Order</flux:label>
                        <flux:input type="number" wire:model="formSortOrder" min="0" max="9999" />
                        <flux:error name="formSortOrder" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Active</flux:label>
                        <div class="mt-2">
                            <flux:switch wire:model="formIsActive" />
                        </div>
                        <flux:error name="formIsActive" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                    <flux:button type="button" variant="ghost" wire:click="$set('showFormModal', false)">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                        {{ $editingId ? 'Update' : 'Create' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="w-full max-w-sm">
        <div class="space-y-4">
            <flux:heading size="lg">Delete Rate Type?</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                This will permanently remove the rate type. Proceed only if no employee rates are assigned to it.
            </flux:text>
            <div class="flex justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="$set('showDeleteModal', false)">
                    Cancel
                </flux:button>
                <flux:button
                    type="button"
                    variant="primary"
                    class="bg-red-600 hover:bg-red-700 focus:ring-red-500"
                    wire:click="deleteType"
                    wire:loading.attr="disabled"
                    wire:target="deleteType"
                >
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>

</section>
