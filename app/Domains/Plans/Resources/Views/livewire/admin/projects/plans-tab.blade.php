<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="lg">Plans</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Upload drawing sets and browse rendered sheets.</flux:text>
        </div>
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search plan sets..." />
    </div>

    @if (session('success'))
        <flux:callout variant="success">{{ session('success') }}</flux:callout>
    @endif

    @if ($canUploadPlans)
        <form wire:submit="save" wire:key="plans-upload-form" class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-4">
            <flux:field>
                <flux:label>Set name</flux:label>
                <flux:input wire:model="name" placeholder="Permit set" />
                <flux:error name="name" />
            </flux:field>
            <flux:field>
                <flux:label>Discipline</flux:label>
                <flux:input wire:model="discipline" placeholder="Architectural" />
                <flux:error name="discipline" />
            </flux:field>
            <flux:field>
                <flux:label>PDF drawing set</flux:label>
                <div class="space-y-1">
                    <input type="file" wire:model="file" accept="application/pdf" wire:key="plans-file-input" class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                    <div wire:loading wire:target="file" class="text-xs font-medium text-sky-600 dark:text-sky-400">
                        Uploading PDF file&hellip;
                    </div>
                    @if ($file)
                        <div class="text-xs text-emerald-600 dark:text-emerald-400">
                            Selected: {{ method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'PDF File' }}
                        </div>
                    @endif
                </div>
                <flux:error name="file" />
            </flux:field>
            <div class="flex items-end">
                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="save,file">
                    <span wire:loading.remove wire:target="file">Upload set</span>
                    <span wire:loading wire:target="file">Uploading&hellip;</span>
                </flux:button>
            </div>
        </form>
    @endif

    <div @if ($polling) wire:poll.2s @endif class="space-y-4">
        @forelse ($sets as $set)
        <div wire:key="plan-set-{{ $set->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <flux:text class="font-semibold">{{ $set->name }}</flux:text>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ $set->discipline ?: 'Unassigned' }} · {{ $set->page_count }} pages</flux:text>
                </div>
                <div class="flex items-center gap-2">
                    <flux:badge>{{ str($set->status)->headline() }}</flux:badge>
                    @if ($canDeletePlans)
                        <flux:button
                            wire:click="deletePlanSet('{{ $set->id }}')"
                            wire:confirm="Are you sure you want to delete this plan set? All associated revisions will be removed."
                            variant="ghost"
                            size="sm"
                            class="text-red-600 hover:text-red-700 dark:text-red-400">
                            Delete
                        </flux:button>
                    @endif
                </div>
            </div>
            @if ($set->status === \App\Domains\Plans\Models\PlanSet::STATUS_RENDERING)
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                    <div class="h-full rounded-full bg-sky-500" style="width: {{ $set->progressPercent() }}%"></div>
                </div>
                <flux:text class="mt-1 text-xs">{{ $set->processed_page_count }} / {{ $set->page_count }} pages rendered</flux:text>
            @elseif ($set->status === \App\Domains\Plans\Models\PlanSet::STATUS_FAILED)
                <flux:callout class="mt-3" variant="danger">{{ $set->error_message ?: 'Processing failed.' }}</flux:callout>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
            <flux:heading size="sm">No plan sets yet</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Upload a PDF drawing set to start building the sheet index.</flux:text>
        </div>
    @endforelse
    </div>

    <livewire:plans::sheets.index :project="$project" :key="'plans-sheet-index-'.$project->id" />
</div>
