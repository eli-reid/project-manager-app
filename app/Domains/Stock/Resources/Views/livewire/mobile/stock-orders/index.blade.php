@php
    $statusColors = [
        'pending' => 'border-amber-800/60 bg-amber-950/30 text-amber-300',
        'approved' => 'border-blue-800/60 bg-blue-950/30 text-blue-300',
        'ordered' => 'border-indigo-800/60 bg-indigo-950/30 text-indigo-300',
        'received' => 'border-emerald-800/60 bg-emerald-950/30 text-emerald-300',
        'cancelled' => 'border-zinc-700 bg-zinc-800 text-zinc-400',
    ];
@endphp

<div class="flex flex-col gap-4 px-4 py-5 pb-28">
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Stock Orders') }}</p>
                <p class="mt-1 text-sm text-zinc-300">{{ __('Track requests and order status from the field.') }}</p>
            </div>

            @can('create', \App\Domains\Stock\Models\StockOrder::class)
                <a
                    href="{{ route('stock-orders.mobile.create') }}"
                    class="inline-flex min-h-10 items-center justify-center rounded-xl border border-zinc-700 bg-zinc-800 px-3 text-xs font-semibold text-zinc-100 active:bg-zinc-700"
                    wire:navigate
                    data-mobile-haptic
                >
                    {{ __('New Order') }}
                </a>
            @endcan
        </div>

        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2">
            <select wire:model.live="filterStatus" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none">
                <option value="">{{ __('All Statuses') }}</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterUrgency" class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3 py-2.5 text-sm text-zinc-100 focus:border-zinc-500 focus:outline-none">
                <option value="">{{ __('All Urgencies') }}</option>
                @foreach ($urgencies as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <button
            type="button"
            wire:click="$set('filterStatus', ''); $set('filterUrgency', '')"
            class="mt-2 inline-flex min-h-10 items-center rounded-xl border border-zinc-700 px-3 text-xs font-semibold text-zinc-300 active:bg-zinc-800"
            data-mobile-haptic
        >
            {{ __('Reset Filters') }}
        </button>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col gap-3">
        @forelse ($orders as $order)
            <a
                href="{{ route('stock-orders.mobile.show', $order) }}"
                class="flex items-center justify-between rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4 active:bg-zinc-800"
                wire:key="mobile-stock-order-{{ $order->id }}"
                wire:navigate
                data-mobile-haptic
            >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-zinc-100">{{ $order->po_number ?: __('No PO number') }}</p>
                    <p class="mt-0.5 truncate text-xs text-zinc-400">
                        {{ $order->project?->name ?? __('No project linked') }}
                        @if ($order->project?->project_number)
                            &middot; #{{ $order->project->project_number }}
                        @endif
                    </p>
                    <p class="mt-0.5 text-xs text-zinc-500">
                        {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}
                        &middot;
                        {{ __('Urgency: :urgency', ['urgency' => ucfirst($order->urgency)]) }}
                    </p>
                </div>

                <div class="ml-3 shrink-0 flex items-center gap-3">
                    <span class="rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $statusColors[$order->status] ?? 'border-zinc-700 bg-zinc-800 text-zinc-300' }}">
                        {{ ucfirst($order->status) }}
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-zinc-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-10 text-center">
                <p class="text-sm font-medium text-zinc-400">{{ __('No stock orders found.') }}</p>
            </div>
        @endforelse
    </div>

    @if ($orders->hasPages())
        <div class="flex justify-center gap-4 pt-2">
            @if ($orders->onFirstPage())
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Previous') }}</span>
            @else
                <button type="button" wire:click="previousPage" wire:loading.attr="disabled" wire:target="previousPage" class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:opacity-60" data-mobile-haptic>{{ __('Previous') }}</button>
            @endif

            @if ($orders->hasMorePages())
                <button type="button" wire:click="nextPage" wire:loading.attr="disabled" wire:target="nextPage" class="rounded-full border border-zinc-700 px-4 py-2 text-xs font-semibold text-zinc-300 active:bg-zinc-800 disabled:opacity-60" data-mobile-haptic>{{ __('Next') }}</button>
            @else
                <span class="rounded-full border border-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-600">{{ __('Next') }}</span>
            @endif
        </div>
    @endif
</div>
