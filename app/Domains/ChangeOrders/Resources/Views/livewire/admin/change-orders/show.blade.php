<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $changeOrder->project?->name }} &middot; Change Order</p>
            <h1 class="mt-1 text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $changeOrder->title }}</h1>
        </div>
        <a href="{{ $backUrl !== '' ? $backUrl : route('admin.change-orders.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Back</a>
    </div>

    <div class="grid grid-cols-2 gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:grid-cols-4">
        <div>
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Status</p>
            <p class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">{{ ucfirst($changeOrder->status) }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Labor</p>
            <p class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">${{ number_format((float) $changeOrder->labor_amount, 2) }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Materials</p>
            <p class="mt-0.5 text-sm text-zinc-800 dark:text-zinc-200">${{ number_format((float) $changeOrder->materials_amount, 2) }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total</p>
            <p class="mt-0.5 text-sm font-semibold text-zinc-800 dark:text-zinc-200">${{ number_format((float) $changeOrder->total_amount, 2) }}</p>
        </div>
    </div>

    @if ($changeOrder->description)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</h2>
            <p class="whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $changeOrder->description }}</p>
        </div>
    @endif

    @if ($changeOrder->documents->isNotEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Attached Documents</h2>
            <ul class="space-y-2">
                @foreach ($changeOrder->documents as $document)
                    <li class="rounded-md border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium text-zinc-800 dark:text-zinc-100">{{ $document->title }}</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst((string) ($document->pivot?->document_role ?? 'reference')) }} &middot; {{ ucfirst((string) ($document->pivot?->document_status ?? 'active')) }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-100">Actions</h2>

        @if ($changeOrder->status === \App\Domains\ChangeOrders\Models\ChangeOrder::STATUS_SUBMITTED)
            <div class="mb-3">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Rejection Reason (optional)</label>
                <textarea wire:model="rejectionReason" rows="2" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            @can('submit', $changeOrder)
                <button wire:click="submit" class="rounded-md bg-sky-700 px-3 py-2 text-xs font-semibold text-white hover:bg-sky-600">Submit</button>
            @endcan
            @can('approve', $changeOrder)
                <button wire:click="approve" class="rounded-md bg-green-700 px-3 py-2 text-xs font-semibold text-white hover:bg-green-600">Approve</button>
            @endcan
            @can('reject', $changeOrder)
                <button wire:click="reject" class="rounded-md bg-red-700 px-3 py-2 text-xs font-semibold text-white hover:bg-red-600">Reject</button>
            @endcan
            @can('implement', $changeOrder)
                <button wire:click="implement" class="rounded-md bg-violet-700 px-3 py-2 text-xs font-semibold text-white hover:bg-violet-600">Implement</button>
            @endcan
            @can('cancel', $changeOrder)
                <button wire:click="cancel" class="rounded-md bg-zinc-700 px-3 py-2 text-xs font-semibold text-white hover:bg-zinc-600">Cancel</button>
            @endcan
            @can('update', $changeOrder)
                <a href="{{ $embedded ? app(\App\Domains\Projects\Services\ProjectTabLinkBuilder::class)->to((string) $changeOrder->project_id, 'change-orders', mode: 'create', detailId: (string) $changeOrder->id) : route('admin.change-orders.edit', $changeOrder) }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</a>
            @endcan
        </div>
    </div>
</div>
