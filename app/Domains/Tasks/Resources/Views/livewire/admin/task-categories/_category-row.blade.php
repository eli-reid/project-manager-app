@foreach ($categories as $category)
    <tr wire:key="category-{{ $category->id }}">
        <td class="py-3 align-top text-sm font-medium text-zinc-900 dark:text-zinc-100" style="padding-left: {{ ($depth * 24) + 16 }}px">
            @if ($depth > 0)
                <span class="mr-1 text-zinc-400">↳</span>
            @endif
            {{ $category->name }}
        </td>
        <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $category->project?->name ?? 'Global' }}</td>
        <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $category->description ?? '—' }}</td>
        <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
            <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $category->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                {{ $category->is_active ? 'Active' : 'Inactive' }}
            </span>
        </td>
        <td class="px-4 py-3 align-top">
            <div class="flex items-center justify-end gap-2">
                @can('update', $category)
                    <a href="{{ route('admin.task-categories.edit', $category) }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
                @endcan
                @can('delete', $category)
                    <button type="button" wire:click="deleteCategory('{{ $category->id }}')" wire:confirm="Delete this category? This will fail if it has children or tasks." class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20">Delete</button>
                @endcan
            </div>
        </td>
    </tr>
    @if ($category->childrenRecursive->isNotEmpty())
        @include('tasks::livewire.admin.task-categories._category-row', ['categories' => $category->childrenRecursive, 'depth' => $depth + 1])
    @endif
@endforeach
