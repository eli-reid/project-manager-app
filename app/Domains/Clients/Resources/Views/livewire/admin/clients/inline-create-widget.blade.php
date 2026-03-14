<div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/40">
    <div class="mb-2 flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Quick Add Client</p>
        @if (! $showForm)
            <button type="button" wire:click="open" class="rounded-md border border-zinc-300 px-2 py-1 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Add Client</button>
        @endif
    </div>

    @if ($showForm)
        <div class="grid gap-2 md:grid-cols-2">
            <div class="md:col-span-2">
                <input type="text" wire:model="company_name" placeholder="Company Name" class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                @error('company_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <input type="text" wire:model="contact_name" placeholder="Contact Name" class="rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            <input type="email" wire:model="email" placeholder="Email" class="rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
            <input type="text" wire:model="phone" placeholder="Phone" class="md:col-span-2 rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
        </div>

        <div class="mt-3 flex justify-end gap-2">
            <button type="button" wire:click="cancel" class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
            <button type="button" wire:click="saveInline" class="rounded-md bg-zinc-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">Save Client</button>
        </div>
    @endif
</div>
