<div class="space-y-6">
    @if ($embeddedProject)
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Project Dailies</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $reports->total() }} {{ \Illuminate\Support\Str::plural('daily report', $reports->total()) }} linked to this project.
                </p>
            </div>

            <a href="{{ route('admin.dailies.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Open Queue</a>
        </div>
    @else
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Daily Reports') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Review and manage all field daily reports.') }}</flux:text>
            </div>

            @can('create', \App\Domains\Dailies\Models\DailyReport::class)
                <flux:button :href="route('admin.dailies.create')" wire:navigate icon="plus" variant="primary">
                    {{ __('New Report') }}
                </flux:button>
            @endcan
        </div>
    @endif

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm md:grid-cols-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</label>
            <select wire:model.live="statusFilter" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="{{ \App\Domains\Dailies\Models\DailyReport::STATUS_DRAFT }}">{{ __('Draft') }}</option>
                <option value="{{ \App\Domains\Dailies\Models\DailyReport::STATUS_SUBMITTED }}">{{ __('Submitted') }}</option>
                <option value="{{ \App\Domains\Dailies\Models\DailyReport::STATUS_APPROVED }}">{{ __('Approved') }}</option>
                <option value="{{ \App\Domains\Dailies\Models\DailyReport::STATUS_REJECTED }}">{{ __('Rejected') }}</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('From Date') }}</label>
            <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('To Date') }}</label>
            <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
        </div>

        <div class="flex items-end">
            <button
                type="button"
                wire:click="$set('statusFilter', ''); $set('dateFrom', ''); $set('dateTo', '')"
                class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                {{ __('Reset Filters') }}
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Worker') }}</th>
                        @unless ($embeddedProject)
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Project') }}</th>
                        @endunless
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Hours') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        <th class="relative px-4 py-3"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($reports as $report)
                        <tr wire:key="admin-daily-report-{{ $report->id }}" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ optional($report->report_date)->format('M j, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                {{ trim(($report->user?->first_name ?? '').' '.($report->user?->last_name ?? '')) ?: '—' }}
                            </td>
                            @unless ($embeddedProject)
                                <td class="max-w-40 truncate px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $report->project?->name ?? $report->custom_project_name ?? '—' }}
                                </td>
                            @endunless
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-zinc-700 dark:text-zinc-300">
                                {{ number_format($report->total_hours, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @php
                                    $badge = match($report->status) {
                                        'approved'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                        'submitted' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                        'rejected'  => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                        default     => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge }}">
                                    {{ str($report->status)->headline() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ $embeddedProject ? route('admin.projects.show', ['project' => $embeddedProject, 'tab' => 'dailies', 'dailyId' => $report->id]) : route('admin.dailies.show', $report) }}" wire:navigate class="rounded px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                                        {{ $report->status === \App\Domains\Dailies\Models\DailyReport::STATUS_SUBMITTED ? __('Review') : __('View') }}
                                    </a>
                                    @can('update', $report)
                                        <a href="{{ route('admin.dailies.edit', $report) }}" wire:navigate class="rounded px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            {{ __('Edit') }}
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $embeddedProject ? 5 : 6 }}" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No daily reports found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>
