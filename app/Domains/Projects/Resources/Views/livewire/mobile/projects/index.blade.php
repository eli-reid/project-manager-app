<div class="flex flex-col gap-4 px-4 py-5 pb-28">
    <div class="rounded-3xl border border-zinc-800 bg-zinc-900 px-4 py-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-500">{{ __('Project Access') }}</p>
                <h2 class="mt-1 text-lg font-semibold text-zinc-50">{{ __('Projects') }}</h2>
                <p class="mt-1 text-sm text-zinc-400">
                    {{ __('Browse active jobs, then jump to details or shared drawings and specs.') }}
                </p>
            </div>

            <span class="rounded-full border border-zinc-700 bg-zinc-800 px-3 py-1 text-xs font-semibold text-zinc-300">
                {{ $projects->total() }}
            </span>
        </div>

        <div class="mt-4 grid gap-3">
            <flux:field>
                <flux:label>{{ __('Search') }}</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Project name, number, or city') }}" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Visibility') }}</flux:label>
                <flux:select wire:model.live="visibilityScope">
                    <option value="assigned">{{ __('Assigned Only') }}</option>
                    <option value="permitted">{{ __('Broader Permitted') }}</option>
                </flux:select>
            </flux:field>
        </div>
    </div>

    <div class="flex flex-col gap-3">
        @forelse ($projects as $project)
            <article
                class="rounded-3xl border border-zinc-800 bg-zinc-900 p-4 shadow-sm"
                wire:key="mobile-project-{{ $project->id }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <a
                            href="{{ route('projects.mobile.show', $project) }}"
                            class="block"
                            wire:navigate
                            data-mobile-haptic
                        >
                            <h3 class="truncate text-base font-semibold text-zinc-50">{{ $project->name }}</h3>
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Project #: :number', ['number' => $project->project_number ?? 'N/A']) }}</p>
                        </a>
                    </div>

                    <span class="shrink-0 rounded-full border border-zinc-700 bg-zinc-800 px-2.5 py-1 text-[11px] font-semibold text-zinc-300">
                        {{ $project->status?->label() ?? __('Unknown') }}
                    </span>
                </div>

                <div class="mt-4 rounded-2xl border border-zinc-800/80 bg-zinc-950/60 px-3 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Job Site') }}</p>

                    @if ($project->address)
                        <p class="mt-2 text-sm text-zinc-200">{{ $project->address->address1 }}</p>

                        @if ($project->address->address2)
                            <p class="text-sm text-zinc-400">{{ $project->address->address2 }}</p>
                        @endif

                        <p class="mt-1 text-sm text-zinc-400">
                            {{ collect([$project->address->city, $project->address->state, $project->address->zip])->filter()->implode(', ') }}
                        </p>
                    @else
                        <p class="mt-2 text-sm text-zinc-500">{{ __('No address on file.') }}</p>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a
                        href="{{ route('projects.mobile.show', $project) }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-zinc-700 bg-zinc-800 px-4 text-sm font-semibold text-zinc-100 active:bg-zinc-700"
                        wire:navigate
                        data-mobile-haptic
                    >
                        {{ __('Open Project') }}
                    </a>

                    <a
                        href="{{ route('documents.mobile.global') }}"
                        class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-sky-800/70 bg-sky-950/40 px-4 text-sm font-semibold text-sky-200 active:bg-sky-900/60"
                        wire:navigate
                        data-mobile-haptic
                    >
                        {{ __('Documents') }}
                    </a>
                </div>

                @if ($project->address)
                    <a
                        href="{{ 'https://www.google.com/maps/search/?api=1&query=' . urlencode(collect([
                            $project->address->address1,
                            $project->address->address2,
                            $project->address->city,
                            $project->address->state,
                            $project->address->zip,
                            $project->address->country,
                        ])->filter()->implode(', ')) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex min-h-10 items-center justify-center rounded-2xl border border-zinc-800 px-4 text-xs font-semibold uppercase tracking-[0.18em] text-zinc-400 active:bg-zinc-800"
                        data-mobile-haptic
                    >
                        {{ __('Open in Maps') }}
                    </a>
                @endif
            </article>
        @empty
            <div class="rounded-3xl border border-zinc-800 bg-zinc-900 px-4 py-10 text-center">
                <svg class="mx-auto h-10 w-10 text-zinc-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M5.25 21V7.5a.75.75 0 0 1 .75-.75h12a.75.75 0 0 1 .75.75V21M9 9.75h6M9 13.5h6M9 17.25h3" />
                </svg>
                <p class="mt-3 text-sm font-medium text-zinc-400">{{ __('No projects found for the selected filters.') }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Try a different search or visibility scope.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($projects->hasPages())
        <div class="flex justify-center gap-4 pt-2">
            @if ($projects->onFirstPage())
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Previous') }}</span>
            @else
                <button wire:click="previousPage" class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800" data-mobile-haptic>{{ __('Previous') }}</button>
            @endif

            @if ($projects->hasMorePages())
                <button wire:click="nextPage" class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800" data-mobile-haptic>{{ __('Next') }}</button>
            @else
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Next') }}</span>
            @endif
        </div>
    @endif
</div>