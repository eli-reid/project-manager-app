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

<x-slot:headerAction>
    <div class="flex items-center gap-2">
        @can('update', $stockOrder)
            <a
                href="{{ route('stock-orders.mobile.edit', $stockOrder) }}"
                class="inline-flex min-h-10 items-center justify-center rounded-xl border border-zinc-700 px-3 text-xs font-semibold text-zinc-200 active:bg-zinc-800"
                wire:navigate
                data-mobile-haptic
            >
                {{ __('Edit') }}
            </a>
        @endcan
    </div>
</x-slot:headerAction>

<div class="flex flex-col gap-4 px-4 py-5 pb-24">
    @if (session('success'))
        <div class="rounded-2xl border border-emerald-700/40 bg-emerald-600/20 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Order Overview') }}</p>

        <div class="mt-2 flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="truncate text-xl font-semibold text-zinc-100">
                    {{ $stockOrder->po_number ?: __('No PO number') }}
                </h2>
                <p class="mt-1 text-sm text-zinc-400">
                    {{ $stockOrder->project?->name ?? __('No project linked') }}
                    @if ($stockOrder->project?->project_number)
                        &middot; #{{ $stockOrder->project->project_number }}
                    @endif
                </p>
            </div>

            <span class="inline-flex shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $statusColors[$stockOrder->status] ?? 'border-zinc-700 bg-zinc-800 text-zinc-300' }}">
                {{ ucfirst($stockOrder->status) }}
            </span>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-zinc-800 bg-zinc-950/70 px-3 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Urgency') }}</p>
                <p class="mt-1 text-sm font-semibold text-zinc-100">{{ ucfirst($stockOrder->urgency) }}</p>
            </div>

            <div class="rounded-xl border border-zinc-800 bg-zinc-950/70 px-3 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Items') }}</p>
                <p class="mt-1 text-sm font-semibold text-zinc-100">{{ $stockOrder->items->count() }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-zinc-100">{{ __('Request Details') }}</p>
            <span class="text-xs text-zinc-500">{{ $stockOrder->created_at->format('M j, Y') }}</span>
        </div>

        <dl class="space-y-3 text-sm">
            <div class="flex items-start justify-between gap-4 border-b border-zinc-800 pb-3">
                <dt class="text-zinc-500">{{ __('Submitted by') }}</dt>
                <dd class="text-right text-zinc-200">{{ $stockOrder->user?->name ?? __('Unknown') }}</dd>
            </div>

            <div class="flex items-start justify-between gap-4 border-b border-zinc-800 pb-3">
                <dt class="text-zinc-500">{{ __('Project') }}</dt>
                <dd class="text-right text-zinc-200">
                    {{ $stockOrder->project?->name ?? __('No project linked') }}
                </dd>
            </div>

            <div class="flex items-start justify-between gap-4 border-b border-zinc-800 pb-3">
                <dt class="text-zinc-500">{{ __('PO Number') }}</dt>
                <dd class="text-right text-zinc-200">{{ $stockOrder->po_number ?: __('Not set') }}</dd>
            </div>

            <div class="flex items-start justify-between gap-4">
                <dt class="text-zinc-500">{{ __('Urgency') }}</dt>
                <dd>
                    <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold {{ $urgencyColors[$stockOrder->urgency] ?? 'border-zinc-700 bg-zinc-800 text-zinc-300' }}">
                        {{ ucfirst($stockOrder->urgency) }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-zinc-100">{{ __('Items') }}</p>
            <span class="text-xs text-zinc-500">{{ $stockOrder->items->count() }}</span>
        </div>

        @if ($stockOrder->items->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-800 bg-zinc-950/70 px-4 py-8 text-center">
                <p class="text-sm font-medium text-zinc-400">{{ __('No items were added to this order.') }}</p>
            </div>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($stockOrder->items as $item)
                    <div wire:key="mobile-stock-order-item-{{ $item->id }}" class="rounded-2xl border border-zinc-800 bg-zinc-950/70 px-4 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-zinc-100">{{ $item->item_name }}</p>
                                @if ($item->notes)
                                    <p class="mt-1 text-xs text-zinc-400">{{ $item->notes }}</p>
                                @endif
                            </div>

                            <div class="shrink-0 text-right">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Qty') }}</p>
                                <p class="text-lg font-semibold text-zinc-100">{{ $item->quantity }}</p>
                            </div>
                        </div>

                        <div class="mt-3">
                            <span class="inline-flex rounded-full border border-zinc-700 bg-zinc-800 px-2.5 py-0.5 text-[11px] font-semibold text-zinc-300">
                                {{ ucfirst($item->status) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($stockOrder->notes)
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900 px-4 py-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-500">{{ __('Notes') }}</p>
            <p class="mt-2 whitespace-pre-line text-sm text-zinc-300">{{ $stockOrder->notes }}</p>
        </div>
    @endif
</div>
   
