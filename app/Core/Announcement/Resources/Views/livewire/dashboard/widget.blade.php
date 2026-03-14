<section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
    <div class="mb-4 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Company Announcements</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Latest updates for the team.</p>
        </div>

        @can('create', \App\Core\Announcement\Models\Announcement::class)
            <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                New Announcement
            </a>
        @endcan
    </div>

    <div class="space-y-3">
        @forelse ($announcements as $announcement)
            <article wire:key="dashboard-announcement-{{ $announcement->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <div class="mb-1 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $announcement->title }}</h3>
                    <span class="rounded-full bg-zinc-100 px-2 py-1 text-[10px] font-medium uppercase tracking-wide text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $announcement->type->label() }}</span>
                </div>
                <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $announcement->content }}</p>
            </article>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">No announcements right now.</p>
        @endforelse
    </div>
</section>
