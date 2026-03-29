<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">My Documents</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Private and global documents you own.</flux:text>
        </div>

        <a href="{{ route('documents.global') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Browse Global</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</label>
                    <input type="text" wire:model="title" class="w-full rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">File</label>
                    <input type="file" wire:model="file" class="w-full rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                    @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-3">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</label>
                <textarea wire:model="description" rows="3" class="w-full rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-4 flex items-center gap-2">
                <button type="button" wire:click="save" class="rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                    {{ $editingDocumentId ? 'Update Document' : 'Upload Document' }}
                </button>

                @if ($editingDocumentId)
                    <button type="button" wire:click="cancelEdit" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</button>
                @endif
            </div>
        </div>

        <div class="w-full lg:w-72">
            <input type="text" wire:model.live="search" placeholder="Search documents..." class="w-full rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Visibility</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">File</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($documents as $document)
                        <tr wire:key="user-document-{{ $document->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $document->title }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ str($document->visibility)->headline() }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ $document->original_name }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <button type="button" wire:click="edit('{{ $document->id }}')" class="rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Edit</button>
                                    @if ($document->visibility === \App\Domains\Documents\Models\Document::VISIBILITY_PRIVATE)
                                        <button type="button" wire:click="promote('{{ $document->id }}')" class="rounded-md border border-emerald-300 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">Make Global</button>
                                    @else
                                        <button type="button" wire:click="demote('{{ $document->id }}')" class="rounded-md border border-amber-300 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/20">Make Private</button>
                                    @endif
                                    <button type="button" wire:click="delete('{{ $document->id }}')" wire:confirm="Delete this document?" class="rounded-md border border-red-300 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/20">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No documents uploaded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
