<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Global Documents</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">User-owned documents shared globally.</flux:text>
        </div>

        <a href="{{ route('documents.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">My Documents</a>
    </div>

    <div class="w-full md:w-80">
        <input type="text" wire:model.live="search" placeholder="Search global documents..." class="w-full rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">File</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Owner</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Published</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($documents as $document)
                        <tr wire:key="global-document-{{ $document->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $document->title }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ $document->original_name }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ trim(($document->uploadedBy?->first_name ?? '').' '.($document->uploadedBy?->last_name ?? '')) ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $document->updated_at?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No global documents available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
