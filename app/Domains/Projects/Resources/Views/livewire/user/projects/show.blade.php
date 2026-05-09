<section class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $project->name }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Project #: :number', ['number' => $project->project_number ?? 'N/A']) }}
            </flux:text>
        </div>

        <flux:button variant="ghost" :href="route('projects.index')">
            {{ __('Back to Projects') }}
        </flux:button>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:heading size="sm">{{ __('Project Info') }}</flux:heading>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            <div class="flex items-start gap-4 px-4 py-3">
                <span class="w-32 shrink-0 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</span>
                <span class="text-sm text-zinc-900 dark:text-zinc-100">{{ $project->status?->label() ?? '—' }}</span>
            </div>
            <div class="flex items-start gap-4 px-4 py-3">
                <span class="w-32 shrink-0 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Timeline') }}</span>
                <span class="text-sm text-zinc-900 dark:text-zinc-100">
                    {{ $project->start_date?->format('M j, Y') ?? 'TBD' }}
                    <span class="mx-1 text-zinc-400">{{ __('to') }}</span>
                    {{ $project->end_date?->format('M j, Y') ?? 'TBD' }}
                </span>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <flux:heading size="sm">{{ __('Job Site Address') }}</flux:heading>
        </div>
        @if ($project->address)
            <div class="space-y-1 px-4 py-4 text-sm text-zinc-900 dark:text-zinc-100">
                <p>{{ $project->address->address1 }}</p>
                @if ($project->address->address2)
                    <p>{{ $project->address->address2 }}</p>
                @endif
                <p>
                    {{ $project->address->city }}@if ($project->address->city && $project->address->state),@endif
                    {{ $project->address->state }}
                    {{ $project->address->zip }}
                </p>
                @if ($project->address->country)
                    <p class="text-zinc-500 dark:text-zinc-400">{{ $project->address->country }}</p>
                @endif
                <div class="pt-2">
                    <flux:button
                        size="sm"
                        variant="ghost"
                        :href="'https://maps.google.com/?q='.urlencode(implode(', ', array_filter([$project->address->address1, $project->address->city, $project->address->state, $project->address->zip])))"
                        target="_blank"
                    >
                        {{ __('Open in Maps') }}
                    </flux:button>
                </div>
            </div>
        @else
            <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('No address on file for this project.') }}
            </div>
        @endif
    </div>
</section>
        <!-- Project Documents Section -->
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700 flex items-center justify-between">
                <flux:heading size="sm">{{ __('Project Documents') }}</flux:heading>
                <a
                    href="{{ route('documents.global', ['project_id' => $project->id]) }}"
                    class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300"
                    wire:navigate
                >
                    {{ __('View all') }}
                </a>
            </div>
            <div class="px-4 py-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Drawings, specifications, and other project documents shared by your team.') }}
                </p>
                <a
                    href="{{ route('documents.global', ['project_id' => $project->id]) }}"
                    class="mt-3 inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200"
                    wire:navigate
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    {{ __('Browse Project Documents') }}
                </a>
            </div>
        </div>
    </section>
