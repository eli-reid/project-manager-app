<div class="mx-auto max-w-3xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ $rfi->project?->name }} &middot; RFI #{{ $rfi->number }}
            </p>
            <h1 class="mt-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $rfi->subject }}</h1>
        </div>

        <div class="flex items-center gap-2">
            @php
                $statusColor = match ($rfi->status) {
                    'answered' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                    'submitted' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                    'closed'    => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                    'cancelled' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                    default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                };
            @endphp
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusColor }}">
                {{ ucfirst($rfi->status) }}
            </span>

            @can('cancel', $rfi)
                <button
                    wire:click="cancel"
                    wire:confirm="Cancel this RFI?"
                    class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >
                    Cancel RFI
                </button>
            @endcan

            @can('close', $rfi)
                <button
                    wire:click="close"
                    wire:confirm="Mark this RFI as closed?"
                    class="rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                >
                    Close RFI
                </button>
            @endcan
        </div>
    </div>

    {{-- Body --}}
    @if ($rfi->body)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</h2>
            <p class="whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $rfi->body }}</p>
        </div>
    @endif

    {{-- Meta --}}
    <div class="grid grid-cols-2 gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:grid-cols-4">
        <div>
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Submitted By</p>
            <p class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $rfi->requestedBy?->full_name ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Due Date</p>
            <p class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $rfi->due_date?->format('M j, Y') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Created</p>
            <p class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $rfi->created_at->format('M j, Y') }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Answered By</p>
            <p class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ $rfi->answeredBy?->full_name ?? '—' }}</p>
        </div>
    </div>

    @if ($rfi->documents->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Attached Documents</h2>

            <ul class="space-y-2">
                @foreach ($rfi->documents as $document)
                    <li class="rounded-md border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $document->title }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ ucfirst((string) ($document->pivot?->document_role ?? \App\Domains\RFIs\Models\RFI::DOCUMENT_ROLE_REFERENCE)) }}
                                &middot;
                                {{ ucfirst((string) ($document->pivot?->document_status ?? \App\Domains\RFIs\Models\RFI::DOCUMENT_STATUS_ACTIVE)) }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Existing Answer --}}
    @if ($rfi->answer)
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-green-700 dark:text-green-400">Answer</h2>
            <p class="whitespace-pre-wrap text-sm text-green-900 dark:text-green-200">{{ $rfi->answer }}</p>

            @if ($rfi->cost_impact || $rfi->schedule_impact_days)
                <div class="mt-3 flex flex-wrap gap-4 border-t border-green-200 pt-3 dark:border-green-800">
                    @if ($rfi->cost_impact)
                        <div>
                            <span class="text-xs text-green-600 dark:text-green-400">Cost Impact:</span>
                            <span class="ml-1 text-xs font-medium text-green-800 dark:text-green-200">${{ number_format((float) $rfi->cost_impact, 2) }}</span>
                        </div>
                    @endif
                    @if ($rfi->schedule_impact_days)
                        <div>
                            <span class="text-xs text-green-600 dark:text-green-400">Schedule Impact:</span>
                            <span class="ml-1 text-xs font-medium text-green-800 dark:text-green-200">{{ $rfi->schedule_impact_days }} {{ Str::plural('day', $rfi->schedule_impact_days) }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Answer Form --}}
    @can('answer', $rfi)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-100">Provide Answer</h2>

            <div class="space-y-4">
                <div>
                    <label for="answer" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Answer <span class="text-red-500">*</span></label>
                    <textarea
                        id="answer"
                        wire:model="answer"
                        rows="5"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-zinc-400 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        placeholder="Provide a detailed answer to this RFI..."
                    ></textarea>
                    @error('answer') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="costImpact" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Cost Impact ($)</label>
                        <input
                            id="costImpact"
                            type="number"
                            step="0.01"
                            wire:model="costImpact"
                            class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                            placeholder="0.00"
                        />
                        @error('costImpact') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="scheduleImpactDays" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Schedule Impact (days)</label>
                        <input
                            id="scheduleImpactDays"
                            type="number"
                            wire:model="scheduleImpactDays"
                            class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                            placeholder="0"
                        />
                        @error('scheduleImpactDays') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        wire:click="answer"
                        wire:loading.attr="disabled"
                        class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                    >
                        Submit Answer
                    </button>
                </div>
            </div>
        </div>
    @endcan
</div>
