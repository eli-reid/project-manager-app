<x-dashboard.widget-card :heading="__('Daily Reports')" :subheading="$canViewAll ? __('Team reporting activity.') : __('Your reporting activity.')">
    <x-slot:action>
        <a
            href="{{ route('dailies.index') }}"
            class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            wire:navigate
        >
            {{ __('Open') }}
        </a>
    </x-slot:action>

    <div class="mb-4 grid grid-cols-3 gap-2">
        <div class="rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800/50">
            <p class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Draft') }}</p>
            <p class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($draftCount) }}</p>
        </div>
        <div class="rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800/50">
            <p class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Submitted') }}</p>
            <p class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($submittedCount) }}</p>
        </div>
        <div class="rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-800/50">
            <p class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Approved') }}</p>
            <p class="mt-0.5 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($approvedCount) }}</p>
        </div>
    </div>

    @php
        $statusColors = [
            'draft' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
            'submitted' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300',
            'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300',
            'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-300',
        ];
    @endphp

    @forelse ($reports as $report)
        <a
            href="{{ route('dailies.show', ['dailyReport' => $report]) }}"
            class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/40"
            wire:navigate
        >
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $report->project?->name ?? __('Custom Project') }}
                </p>
                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $report->report_date?->format('M j, Y') }}
                </p>
            </div>
            <span class="ml-3 rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColors[$report->status] ?? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300' }}">
                {{ str($report->status)->headline() }}
            </span>
        </a>
    @empty
        <p class="py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No reports yet.') }}</p>
    @endforelse
</x-dashboard.widget-card>
