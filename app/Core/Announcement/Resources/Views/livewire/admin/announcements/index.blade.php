<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Announcements</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage company announcements and dashboard notices.</p>
        </div>
        @can('create', \App\Core\Announcement\Models\Announcement::class)
            <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                Create Announcement
            </a>
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
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($announcements as $announcement)
                        <tr wire:key="announcement-{{ $announcement->id }}">
                            <td class="px-4 py-3 align-top text-sm text-zinc-900 dark:text-zinc-100">{{ $announcement->title }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $announcement->type->label() }}</td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $announcement->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-center justify-end gap-2">
                                    @can('update', $announcement)
                                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
                                    @endcan
                                    @can('delete', $announcement)
                                        <button
                                            type="button"
                                            wire:click="deleteAnnouncement('{{ $announcement->id }}')"
                                            wire:confirm="Delete this announcement? This action cannot be undone."
                                            class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20"
                                        >
                                            Delete
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No announcements found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $announcements->links() }}
        </div>
    </div>
</div>
