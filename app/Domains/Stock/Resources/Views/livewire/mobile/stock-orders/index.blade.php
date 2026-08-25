<div class="flex flex-col gap-4 px-4 py-5 pb-28">
    @php
        $statusColors = [
            'pending' => 'border-amber-800/60 bg-amber-950/30 text-amber-300',
            'approved' => 'border-blue-800/60 bg-blue-950/30 text-blue-300',
            'ordered' => 'border-indigo-800/60 bg-indigo-950/30 text-indigo-300',
            'received' => 'border-emerald-800/60 bg-emerald-950/30 text-emerald-300',
            'cancelled' => 'border-zinc-700 bg-zinc-800 text-zinc-400',
        ];
        $urgencyColors = [
            'low' => 'border-zinc-700 bg-zinc-800 text-zinc-300',
            'medium' => 'border-amber-800/60 bg-amber-950/30 text-amber-300',
            'high' => 'border-red-800/60 bg-red-950/30 text-red-300',
        ];
    @endphp

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Stock Orders') }}</p>
                <h2 class="mt-1 text-lg font-semibold text-zinc-100">{{ __('Track requests on the go') }}</h2>
                <p class="mt-1 text-sm text-zinc-400">{{ __('Search by PO number or project, then tap an order for details.') }}</p>
            </div>

            @can('create', \App\Domains\Stock\Models\StockOrder::class)
                <a
                    href="{{ route('stock-orders.mobile.create') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-xl bg-zinc-100 px-3 text-xs font-semibold text-zinc-900 active:bg-zinc-300"
                    wire:navigate
                    data-mobile-haptic
                >
                    {{ __('New') }}
                </a>
            @endcan
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-zinc-800 bg-zinc-950/70 px-3 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Results') }}</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-100">{{ $orders->total() }}</p>
            </div>

            <div class="rounded-xl border border-zinc-800 bg-zinc-950/70 px-3 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('On page') }}</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-100">{{ $orders->count() }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Filters') }}</p>

            @if ($search !== '' || $filterStatus !== '' || $filterUrgency !== '')
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="text-xs font-semibold text-zinc-400 underline decoration-zinc-700 decoration-dotted underline-offset-4"
                    data-mobile-haptic
                >
                    {{ __('Clear') }}
                </button>
            @endif
        </div>

        <div class="mt-3 space-y-3">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search PO or project...') }}"
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 placeholder-zinc-500 focus:border-zinc-500 focus:outline-none"
            />

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <select
                    wire:model.live="filterStatus"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                >
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select
                    wire:model.live="filterUrgency"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-3 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none"
                >
                    <option value="">{{ __('All urgencies') }}</option>
                    @foreach ($urgencies as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-3">
        @forelse ($orders as $order)
            @php
                $statusColor = $statusColors[$order->status] ?? 'border-zinc-700 bg-zinc-800 text-zinc-300';
                $urgencyColor = $urgencyColors[$order->urgency] ?? 'border-zinc-700 bg-zinc-800 text-zinc-300';
            @endphp

            <a
                href="{{ route('stock-orders.mobile.show', $order) }}"
                class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4 active:bg-zinc-800"
                wire:key="mobile-stock-order-{{ $order->id }}"
                wire:navigate
                data-mobile-haptic
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-zinc-100">
                            {{ $order->po_number ?: __('No PO number') }}
                        </p>
                        <p class="mt-1 text-xs text-zinc-400">
                            {{ $order->project?->name ?? __('No project linked') }}
                            @if ($order->project?->project_number)
                                &middot; #{{ $order->project->project_number }}
                            @endif
                        </p>
                    </div>

                    <svg class="mt-1 h-4 w-4 shrink-0 text-zinc-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $statusColor }}">
                        {{ ucfirst($order->status) }}
                    </span>
                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $urgencyColor }}">
                        {{ ucfirst($order->urgency) }}
                    </span>
                </div>

                <div class="mt-3 flex items-center justify-between gap-3 text-xs text-zinc-500">
                    <span>{{ trans_choice(':count item|:count items', $order->items_count) }}</span>
                    <span>{{ $order->created_at->format('M j, Y') }}</span>
                </div>

                @if ($order->notes)
                    <p class="mt-2 text-sm text-zinc-400">{{ \Illuminate\Support\Str::limit($order->notes, 120) }}</p>
                @endif
            </a>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-800 bg-zinc-900 px-4 py-10 text-center">
                <svg class="mx-auto h-10 w-10 text-zinc-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                </svg>
                <p class="mt-3 text-sm font-medium text-zinc-400">{{ __('No stock orders found.') }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('Try clearing the filters or create a new request.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="flex justify-center gap-4 pt-2">
            @if ($orders->onFirstPage())
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Previous') }}</span>
            @else
                <button
                    type="button"
                    wire:click="previousPage"
                    wire:loading.attr="disabled"
                    wire:target="previousPage"
                    class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:opacity-60"
                    data-mobile-haptic
                >
                    {{ __('Previous') }}
                </button>
            @endif

            @if ($orders->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage"
                    wire:loading.attr="disabled"
                    wire:target="nextPage"
                    class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:opacity-60"
                    data-mobile-haptic
                >
                    {{ __('Next') }}
                </button>
            @else
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Next') }}</span>
            @endif
        </div>
    @endif
</div>
