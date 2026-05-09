<x-dashboard.widget-card :subheading="__('Project documents shared by your team.')">
    <x-slot:title>
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Project Documents') }}
            @if ($total > 0)
                <span class="ml-1.5 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/60 dark:text-blue-300">
                    {{ $total }}
                </span>
            @endif
        </h3>
    </x-slot:title>

    <x-slot:action>
        <a
            href="{{ route('documents.global') }}"
            class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
            wire:navigate
        >
            {{ __('View all') }}
        </a>
    </x-slot:action>

    @forelse ($documents as $document)
        <a
            href="{{ route('documents.view', $document) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="flex items-start justify-between rounded-lg px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
        >
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $document->title }}</p>
                @if ($document->ownerProject)
                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $document->ownerProject->name }}</p>
                @endif
            </div>
            <div class="ml-3 shrink-0">
                <svg class="h-4 w-4 text-zinc-400 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </div>
        </a>
    @empty
        <p class="py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No documents available.') }}</p>
    @endforelse

    @if ($total > $documents->count())
        <p class="mt-2 text-center text-xs text-zinc-400 dark:text-zinc-500">
            {{ __('+ :count more', ['count' => $total - $documents->count()]) }}
        </p>
    @endif
</x-dashboard.widget-card>
