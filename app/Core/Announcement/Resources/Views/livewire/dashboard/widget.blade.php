<x-dashboard.widget-card :heading="__('Company Announcements')" :subheading="__('Latest updates for the team.')">
    <x-slot:action>
        @can('create', \App\Core\Announcement\Models\Announcement::class)
            <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                {{ __('New Announcement') }}
            </a>
        @endcan
    </x-slot:action>

    <div class="space-y-3">
        @forelse ($announcements as $announcement)
            <article wire:key="dashboard-announcement-{{ $announcement->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div class="mb-1 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0 flex flex-wrap items-center gap-2">
                        <h3 class="min-w-0 break-words text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $announcement->title }}</h3>
                        <span class="rounded-full px-2 py-1 text-[10px] font-medium uppercase tracking-wide {{ $announcement->type->badgeClass() }}">{{ $announcement->type->label() }}</span>
                    </div>
                    @if ($announcement->is_dismissable)
                        <button
                            type="button"
                            wire:click="dismissAnnouncement('{{ $announcement->id }}')"
                            class="rounded-md border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            {{ __('Dismiss') }}
                        </button>
                    @endif
                </div>
                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $announcement->content }}</p>
            </article>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">No announcements right now.</p>
        @endforelse
    </div>
</x-dashboard.widget-card>
