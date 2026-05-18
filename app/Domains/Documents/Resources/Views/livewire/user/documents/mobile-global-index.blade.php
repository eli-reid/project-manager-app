<div class="flex flex-col gap-4 px-4 py-5 pb-28">
    <div class="rounded-3xl border border-zinc-800 bg-zinc-900 px-4 py-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-500">{{ __('Shared Documents') }}</p>
                @if ($project)
                    <h2 class="mt-1 text-lg font-semibold text-zinc-50">{{ __('Project Documents') }}</h2>
                    <p class="mt-1 text-sm text-zinc-400">
                        {{ __('Showing documents for :project.', ['project' => $project->name]) }}
                    </p>
                @else
                    <h2 class="mt-1 text-lg font-semibold text-zinc-50">{{ __('Global Documents') }}</h2>
                    <p class="mt-1 text-sm text-zinc-400">
                        {{ __('Browse all documents shared globally.') }}
                    </p>
                @endif
            </div>

            <span class="rounded-full border border-zinc-700 bg-zinc-800 px-3 py-1 text-xs font-semibold text-zinc-300">
                {{ count($documents) }}
            </span>
        </div>

        <div class="mt-4">
            <flux:field>
                <flux:label>{{ __('Search') }}</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Document name...') }}" />
            </flux:field>
        </div>
    </div>

    <div class="flex flex-col gap-2">
        @forelse ($documents as $document)
            <article
                class="rounded-2xl border border-zinc-800 bg-zinc-900 p-3 shadow-sm"
                wire:key="mobile-document-{{ $document->id }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-zinc-50">{{ $document->original_name }}</h3>
                        <p class="mt-1 text-xs text-zinc-400">{{ $document->updated_at?->format('M j, Y') ?? '—' }}</p>
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    <a
                        href="{{ route('documents.mobile.view', $document) }}"
                        class="inline-flex flex-1 items-center justify-center rounded-lg border border-zinc-700 bg-zinc-800 px-3 py-2.5 text-xs font-semibold text-zinc-100 active:bg-zinc-700"
                        data-mobile-haptic
                    >
                        {{ __('View') }}
                    </a>

                    <a
                        href="{{ route('documents.mobile.download', $document) }}"
                        class="inline-flex flex-1 items-center justify-center rounded-lg border border-sky-800/70 bg-sky-950/40 px-3 py-2.5 text-xs font-semibold text-sky-200 active:bg-sky-900/60"
                        data-mobile-haptic
                    >
                        {{ __('Download') }}
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-8 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-3 text-sm text-zinc-400">{{ __('No documents found.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($documents->count() > 0 && ! $project)
        <div class="mt-2 rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-3 text-center">
            <a
                href="{{ route('documents.index') }}"
                class="inline-flex items-center text-xs font-semibold text-sky-400 hover:text-sky-300"
                wire:navigate
            >
                {{ __('View all my documents') }}
            </a>
        </div>
    @endif
</div>
