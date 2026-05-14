<div x-data="{ open: @entangle($attributes->wire('model')) }" x-show="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div @click.away="open = false" class="bg-white dark:bg-zinc-900 rounded-lg shadow-lg w-full max-w-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $title ?? 'Send Report as Email' }}
            </h2>
            <button @click="open = false" class="text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">&times;</button>
        </div>
        <form wire:submit.prevent="sendEmail">
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Recipient Email</label>
                <input type="email" wire:model.defer="emailForm.recipient" required class="mt-1 w-full rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100" />
                @error('emailForm.recipient') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Subject</label>
                <input type="text" wire:model.defer="emailForm.subject" required class="mt-1 w-full rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100" />
                @error('emailForm.subject') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Message</label>
                <textarea wire:model.defer="emailForm.body" rows="4" class="mt-1 w-full rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100"></textarea>
                @error('emailForm.body') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="open = false" class="rounded bg-zinc-200 dark:bg-zinc-800 px-4 py-2 text-sm text-zinc-700 dark:text-zinc-200">Cancel</button>
                <button type="submit" class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">Send Email</button>
            </div>
        </form>
    </div>
</div>
