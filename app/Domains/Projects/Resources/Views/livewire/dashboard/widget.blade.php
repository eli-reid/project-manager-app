<x-dashboard.widget-card :subheading="__('Currently active projects.')">
    <x-slot:title>
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Active Projects') }}
            @if ($total > 0)
                <span class="ml-1.5 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">
                    {{ $total }}
                </span>
            @endif
        </h3>
    </x-slot:title>

    <x-slot:action>
        <a
            href="{{ route('projects.index') }}"
            class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            wire:navigate
        >
            {{ __('View all') }}
        </a>
    </x-slot:action>

    @forelse ($projects as $project)
        <a
            href="{{ route('projects.show', $project) }}"
            class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
            wire:navigate
        >
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $project->name }}</p>
                @if ($project->client)
                    <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $project->client->company_name }}</p>
                @endif
            </div>
            <div class="ml-3 shrink-0">
                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $project->status->color() }}">
                    {{ $project->status->label() }}
                </span>
            </div>
        </a>
    @empty
        <p class="py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No active projects.') }}</p>
    @endforelse

    @if ($total > $projects->count())
        <p class="mt-2 text-center text-xs text-zinc-400 dark:text-zinc-500">
            {{ __('+ :count more', ['count' => $total - $projects->count()]) }}
        </p>
    @endif
</x-dashboard.widget-card>
