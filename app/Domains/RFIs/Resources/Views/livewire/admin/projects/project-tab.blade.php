<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">RFIs</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $rfiCount }} {{ Str::plural('RFI', $rfiCount) }} on this project.
            </p>
        </div>

        @can('create', App\Domains\RFIs\Models\RFI::class)
            @if (! $isCreateMode)
                <button
                    wire:click="$set('isCreateMode', true)"
                    class="rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                >
                    + New RFI
                </button>
            @endif
        @endcan
    </div>

    {{-- Inline Create Form --}}
    @if ($isCreateMode)
        <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="mb-4 text-sm font-semibold text-zinc-800 dark:text-zinc-100">New RFI</h3>

            <div class="space-y-4">
                <div>
                    <label for="rfi-subject" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Subject <span class="text-red-500">*</span></label>
                    <input
                        id="rfi-subject"
                        type="text"
                        wire:model="subject"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        placeholder="Brief summary of your question"
                    />
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="rfi-body" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                    <textarea
                        id="rfi-body"
                        wire:model="body"
                        rows="4"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                        placeholder="Provide additional context..."
                    ></textarea>
                    @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="w-48">
                    <label for="rfi-due-date" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Due Date</label>
                    <input
                        id="rfi-due-date"
                        type="date"
                        wire:model="dueDate"
                        class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
                    />
                    @error('dueDate') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-2">
                    <button
                        wire:click="submitRfi"
                        wire:loading.attr="disabled"
                        class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                    >
                        Submit RFI
                    </button>
                    <button
                        wire:click="cancelCreate"
                        class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- RFI List --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Submitted By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Due</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($rfis as $rfi)
                        @php
                            $statusColor = match ($rfi->status) {
                                'answered' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                'submitted' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                'closed'    => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                                'cancelled' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
                                default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                            };
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $rfi->number }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $rfi->subject }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">
                                    {{ ucfirst($rfi->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $rfi->requestedBy?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $rfi->due_date?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('view', $rfi)
                                    <a href="{{ route('admin.rfis.show', $rfi) }}" class="rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                        View
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                No RFIs yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
