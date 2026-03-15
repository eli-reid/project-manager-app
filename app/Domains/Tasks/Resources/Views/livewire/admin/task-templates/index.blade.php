<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Task Templates</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Reusable task structures for quick project setup.</p>
        </div>
        @can('create', \App\Domains\Tasks\Models\TaskTemplate::class)
            <a href="{{ route('admin.task-templates.create') }}" wire:navigate class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Create Template</a>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Tasks</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Created By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($templates as $template)
                        @php
                            $priorityColors = [
                                'low' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
                                'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                'high' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                'urgent' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            ];
                        @endphp
                        <tr wire:key="template-{{ $template->id }}">
                            <td class="px-4 py-3 align-top">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $template->name }}</div>
                                @if ($template->description)
                                    <div class="mt-0.5 max-w-xs truncate text-xs text-zinc-400">{{ $template->description }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $template->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-sm">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $priorityColors[$template->priority] ?? 'bg-zinc-100 text-zinc-700' }}">
                                    {{ ucfirst($template->priority) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ count($template->template_tasks ?? []) }}
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                {{ $template->creator ? $template->creator->first_name . ' ' . $template->creator->last_name : '—' }}
                            </td>
                            <td class="px-4 py-3 align-top text-sm">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $template->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center justify-end gap-2">
                                    @can('update', $template)
                                        <a href="{{ route('admin.task-templates.edit', $template) }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
                                    @endcan
                                    @can('delete', $template)
                                        <button type="button" wire:click="deleteTemplate('{{ $template->id }}')" wire:confirm="Delete this template?" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No templates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $templates->links() }}
        </div>
    </div>
</div>
