<div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Stock Order Templates</h1>
        @can('create', \App\Domains\Stock\Models\StockOrderTemplate::class)
            <a href="{{ route('admin.stock-order-templates.create') }}" wire:navigate class="rounded-md bg-zinc-800 px-3 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-200 dark:text-zinc-900 dark:hover:bg-zinc-300">New Template</a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Urgency</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Items</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Global</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Active</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Created By</th>
                        <th class="relative px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($templates as $template)
                        <tr wire:key="template-{{ $template->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3">
                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $template->name }}</p>
                                @if ($template->description)
                                    <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ Str::limit($template->description, 60) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $urgencyColor = match($template->urgency) {
                                        'high' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                        'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        default => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                    };
                                @endphp
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $urgencyColor }}">{{ ucfirst($template->urgency) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ count($template->template_items ?? []) }}</td>
                            <td class="px-4 py-3">
                                @if ($template->is_global)
                                    <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Yes</span>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($template->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $template->createdBy?->first_name }} {{ $template->createdBy?->last_name }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-ui.row-actions-dropdown label="Template actions" width="w-36" :menu-height="130">
                                    @can('update', $template)
                                        <a href="{{ route('admin.stock-order-templates.edit', $template) }}" wire:navigate class="block px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800" @click="closeMenu()">Edit</a>
                                        <button type="button" wire:click="toggleActive('{{ $template->id }}')" class="block w-full px-3 py-2 text-left text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800" @click="closeMenu()">
                                            {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    @endcan
                                    @can('delete', $template)
                                        <button
                                            type="button"
                                            wire:click="deleteTemplate('{{ $template->id }}')"
                                            wire:confirm="Delete this template? This cannot be undone."
                                            class="block w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                            @click="closeMenu()"
                                        >Delete</button>
                                    @endcan
                                </x-ui.row-actions-dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">No templates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $templates->links() }}
</div>
