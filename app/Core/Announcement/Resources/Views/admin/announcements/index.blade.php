<x-layouts::admin :title="__('Announcements')">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Announcements') }}</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ __('Manage company announcements and dashboard notices.') }}</p>
            </div>
            @can('create', \App\Core\Announcement\Models\Announcement::class)
                <a href="{{ route('admin.announcements.create') }}" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                    {{ __('Create') }}
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">{{ __('Title') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">{{ __('Type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-300">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-950">
                    @forelse ($announcements as $announcement)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $announcement->title }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $announcement->type->label() }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $announcement->is_active ? __('Active') : __('Inactive') }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                <div class="flex items-center gap-3">
                                    @can('update', $announcement)
                                        <a class="text-sm font-medium text-blue-600 hover:text-blue-500" href="{{ route('admin.announcements.edit', $announcement) }}">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('delete', $announcement)
                                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-rose-600 hover:text-rose-500">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No announcements yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $announcements->links() }}
    </div>
</x-layouts::admin>
