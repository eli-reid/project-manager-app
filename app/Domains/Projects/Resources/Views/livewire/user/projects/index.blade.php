<section class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Projects') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Browse active projects and open details for work across tasks, dailies, documents, and costs.') }}
            </flux:text>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-[2fr_1fr]">
        <flux:field>
            <flux:label>{{ __('Search') }}</flux:label>
            <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Project name or number') }}" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Visibility') }}</flux:label>
            <flux:select wire:model.live="visibilityScope">
                <option value="assigned">{{ __('Assigned') }}</option>
                <option value="permitted">{{ __('Broader Permitted') }}</option>
            </flux:select>
        </flux:field>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Project') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Address') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($projects as $project)
                        <tr wire:key="user-project-{{ $project->id }}">
                            <td class="px-4 py-3 align-top">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $project->name }}</div>
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $project->project_number ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">
                                @if ($project->address)
                                    <div>{{ $project->address->address1 }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ collect([$project->address->city, $project->address->state, $project->address->zip])->filter()->implode(', ') }}
                                    </div>
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
                                        class="mt-1 inline-block text-xs font-medium text-zinc-600 underline decoration-zinc-300 underline-offset-2 hover:text-zinc-900 dark:text-zinc-300 dark:decoration-zinc-500 dark:hover:text-zinc-100"
                                    >
                                        {{ __('Open in Maps') }}
                                    </a>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-zinc-700 dark:text-zinc-300">{{ $project->status?->label() ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 align-top text-right">
                                    <a
                                        href="{{ route('documents.global', ['project_id' => $project->id]) }}"
                                        class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
                                        title="{{ __('View project documents') }}"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="hidden sm:inline">{{ __('Documents') }}</span>
                                    </a>
                                </td>
                        </tr>
                    @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No projects found for the selected filters.') }}</td>
                            </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $projects->links() }}
        </div>
    </div>
</section>
