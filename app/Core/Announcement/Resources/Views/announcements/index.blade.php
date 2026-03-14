<x-layouts::app :title="__('Announcements')">
    <div class="space-y-4">
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Announcements') }}</h1>

        <div class="space-y-3">
            @forelse ($announcements as $announcement)
                <article class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <header class="mb-2 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $announcement->title }}</h2>
                        <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $announcement->type->label() }}</span>
                    </header>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $announcement->content }}</p>
                </article>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No announcements right now.') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
