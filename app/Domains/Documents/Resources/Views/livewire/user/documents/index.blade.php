<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">My Documents</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Private and global documents you own.</flux:text>
        </div>

        <flux:button variant="ghost" :href="route('documents.global')" wire:navigate>
            Browse Global
        </flux:button>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.8fr)_minmax(320px,1fr)]">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Upload a document</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Keep your private files organized or update an existing document without leaving this page.</p>
                </div>

                <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    {{ $editingDocumentId ? 'Editing existing document' : 'New upload' }}
                </span>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(250px,0.9fr)]">
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <flux:input wire:model="title" placeholder="Safety checklist" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" rows="5" placeholder="Add context so this is easier to find later." />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                <div class="space-y-3 rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                    <div>
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">File</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Choose a replacement file or keep the current one while editing.</p>
                    </div>

                    <label for="user-document-file" class="flex cursor-pointer flex-col gap-2 rounded-xl border border-zinc-200 bg-white px-4 py-4 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 dark:hover:bg-zinc-900/80">
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Choose file</span>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ optional($file)->getClientOriginalName() ?? ($editingDocumentId ? 'No new file selected. The current file will be kept.' : 'No file selected yet.') }}
                        </span>
                    </label>

                    <input id="user-document-file" type="file" wire:model="file" class="sr-only" />
                    <flux:error name="file" />

                    <div wire:loading wire:target="file" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-700 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300">
                        Uploading selection...
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save,file">
                    {{ $editingDocumentId ? 'Update Document' : 'Upload Document' }}
                </flux:button>

                @if ($editingDocumentId)
                    <flux:button variant="ghost" wire:click="cancelEdit">Cancel</flux:button>
                @endif
            </div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Search your library</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Filter by title or original file name.</p>

                <div class="mt-4">
                    <flux:field>
                        <flux:label>Search documents</flux:label>
                        <flux:input wire:model.live="search" placeholder="Search documents" />
                    </flux:field>
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">At a glance</h2>

                <div class="mt-4 grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total</p>
                        <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $documents->count() }}</p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Private</p>
                        <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $documents->where('visibility', \App\Domains\Documents\Models\Document::VISIBILITY_PRIVATE)->count() }}</p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/60">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Global</p>
                        <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $documents->where('visibility', \App\Domains\Documents\Models\Document::VISIBILITY_GLOBAL)->count() }}</p>
                    </div>
                </div>
            </section>
        </aside>
    </div>

    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Your documents</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Manage visibility, swap files, or remove documents you no longer need.</p>
        </div>

        @if ($documents->isEmpty())
            <div class="px-5 py-14 text-center">
                <p class="text-base font-semibold text-zinc-900 dark:text-zinc-100">No documents uploaded yet.</p>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Upload your first file to start building your personal document library.</p>
            </div>
        @else
            <div class="divide-y divide-zinc-200 md:hidden dark:divide-zinc-800">
                @foreach ($documents as $document)
                    <article wire:key="user-document-card-{{ $document->id }}" class="space-y-4 px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $document->title }}</h3>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $document->original_name }}</p>
                            </div>

                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $document->visibility === \App\Domains\Documents\Models\Document::VISIBILITY_PRIVATE ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                                {{ str($document->visibility)->headline() }}
                            </span>
                        </div>

                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $document->description ?: 'No description added yet.' }}</p>

                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" variant="ghost" wire:click="edit('{{ $document->id }}')">Edit</flux:button>
                            @if ($document->visibility === \App\Domains\Documents\Models\Document::VISIBILITY_PRIVATE)
                                <flux:button size="sm" variant="ghost" wire:click="promote('{{ $document->id }}')" class="border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">Make Global</flux:button>
                            @else
                                <flux:button size="sm" variant="ghost" wire:click="demote('{{ $document->id }}')" class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/20">Make Private</flux:button>
                            @endif
                            <flux:button size="sm" variant="ghost" wire:click="delete('{{ $document->id }}')" wire:confirm="Delete this document?" class="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/20">Delete</flux:button>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
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
                    @foreach ($documents as $document)
                        <tr wire:key="user-document-{{ $document->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $document->title }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $document->description ?: 'No description added yet.' }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ str($document->visibility)->headline() }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ $document->original_name }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-2">
                                    <flux:button size="xs" variant="ghost" wire:click="edit('{{ $document->id }}')">Edit</flux:button>
                                    @if ($document->visibility === \App\Domains\Documents\Models\Document::VISIBILITY_PRIVATE)
                                        <flux:button size="xs" variant="ghost" wire:click="promote('{{ $document->id }}')" class="border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-300 dark:hover:bg-emerald-900/20">Make Global</flux:button>
                                    @else
                                        <flux:button size="xs" variant="ghost" wire:click="demote('{{ $document->id }}')" class="border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/20">Make Private</flux:button>
                                    @endif
                                    <flux:button size="xs" variant="ghost" wire:click="delete('{{ $document->id }}')" wire:confirm="Delete this document?" class="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/20">Delete</flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
        </div>
    </section>
</div>
