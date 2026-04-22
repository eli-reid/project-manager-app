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
        <section
            x-data="{
                titleValue: $wire.entangle('title'),
                selectedFileName: '',
                lastAutoTitle: '',
                isUploading: false,
                uploadProgress: 0,
                fileBaseName(fileName) {
                    return fileName.replace(/\.[^/.]+$/, '')
                },
                syncSelectedFile(fileName) {
                    this.selectedFileName = fileName

                    if (! fileName) {
                        return
                    }

                    const nextTitle = this.fileBaseName(fileName)

                    if (this.titleValue.trim() === '' || this.titleValue === this.lastAutoTitle) {
                        this.titleValue = nextTitle
                        this.lastAutoTitle = nextTitle
                    }
                }
            }"
            x-on:documents-file-input-reset.window="titleValue = ''; selectedFileName = ''; lastAutoTitle = ''; isUploading = false; uploadProgress = 0; $refs.userDocumentFile.value = null"
            x-on:livewire-upload-start="isUploading = true; uploadProgress = 0"
            x-on:livewire-upload-finish="isUploading = false; uploadProgress = 100"
            x-on:livewire-upload-error="isUploading = false; uploadProgress = 0"
            x-on:livewire-upload-cancel="isUploading = false; uploadProgress = 0"
            x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
            class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        >
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
                        <flux:input x-model="titleValue" placeholder="Safety checklist" />
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

                    @php($defaultFileLabel = optional($file)->getClientOriginalName() ?? ($editingDocumentId ? 'No new file selected. The current file will be kept.' : 'No file selected yet.'))

                    <label
                        for="user-document-file"
                        x-bind:class="isUploading ? 'pointer-events-none opacity-75' : ''"
                        class="relative flex cursor-pointer flex-col gap-2 overflow-hidden rounded-xl border border-zinc-200 bg-white px-4 py-4 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600 dark:hover:bg-zinc-900/80"
                    >
                        <div
                            x-show="isUploading"
                            class="absolute inset-y-0 left-0 rounded-xl bg-sky-100/80 transition-[width] duration-200 ease-out dark:bg-sky-900/30"
                            x-bind:style="`width: ${uploadProgress}%`"
                        ></div>

                        <div class="relative z-10 flex flex-col gap-2">
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">Choose file</span>
                        <span x-text="selectedFileName || @js($defaultFileLabel)" class="text-xs text-zinc-500 dark:text-zinc-400"></span>
                        </div>
                    </label>

                    <input id="user-document-file" x-ref="userDocumentFile" type="file" wire:model="file" x-bind:disabled="isUploading" x-on:change="syncSelectedFile($event.target.files?.[0]?.name ?? '')" class="sr-only" />
                    <flux:error name="file" />

                    <div wire:loading wire:target="file" class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-700 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300">
                        <div class="flex items-center justify-between gap-3">
                            <span>Uploading selection...</span>
                            <span x-text="`${uploadProgress}%`" class="font-semibold"></span>
                        </div>

                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-sky-200/80 dark:bg-sky-950">
                            <div class="h-full rounded-full bg-sky-500 transition-[width] duration-200 ease-out dark:bg-sky-400" x-bind:style="`width: ${uploadProgress}%`"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save,file">
                    {{ $editingDocumentId ? 'Update Document' : 'Save Document' }}
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

    <section class="overflow-visible rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
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

                        <div class="flex justify-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="sm" variant="ghost" icon-trailing="ellipsis-horizontal">Actions</flux:button>

                                <flux:menu>
                                    <flux:menu.item as="button" type="button" wire:click="edit('{{ $document->id }}')" icon="pencil-square">Edit</flux:menu.item>
                                    <flux:menu.item :href="route('documents.download', $document)" icon="arrow-down-tray">Download</flux:menu.item>

                                    @can('share', $document)
                                        <flux:menu.item
                                            as="button"
                                            type="button"
                                            wire:click.prevent="openSharePanel('{{ $document->id }}')"
                                            x-on:click.stop
                                            icon="link"
                                        >
                                            Share
                                        </flux:menu.item>
                                    @endcan

                                    @if ($document->visibility === \App\Domains\Documents\Models\Document::VISIBILITY_PRIVATE)
                                        <flux:menu.item as="button" type="button" wire:click="promote('{{ $document->id }}')" icon="globe-alt">Make Global</flux:menu.item>
                                    @else
                                        <flux:menu.item as="button" type="button" wire:click="demote('{{ $document->id }}')" icon="lock-closed">Make Private</flux:menu.item>
                                    @endif

                                    <flux:menu.separator />

                                    <flux:menu.item as="button" type="button" wire:click="delete('{{ $document->id }}')" wire:confirm="Delete this document?" icon="trash" variant="danger">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto overflow-y-visible md:block">
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
                                <div class="inline-flex">
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button size="xs" variant="ghost" icon-trailing="ellipsis-horizontal">Actions</flux:button>

                                        <flux:menu>
                                            <flux:menu.item as="button" type="button" wire:click="edit('{{ $document->id }}')" icon="pencil-square">Edit</flux:menu.item>
                                            <flux:menu.item :href="route('documents.download', $document)" icon="arrow-down-tray">Download</flux:menu.item>

                                            @can('share', $document)
                                                <flux:menu.item
                                                    as="button"
                                                    type="button"
                                                    wire:click.prevent="openSharePanel('{{ $document->id }}')"
                                                    x-on:click.stop
                                                    icon="link"
                                                >
                                                    Share ({{ $document->shares_count }})
                                                </flux:menu.item>
                                            @endcan

                                            @if ($document->visibility === \App\Domains\Documents\Models\Document::VISIBILITY_PRIVATE)
                                                <flux:menu.item as="button" type="button" wire:click="promote('{{ $document->id }}')" icon="globe-alt">Make Global</flux:menu.item>
                                            @else
                                                <flux:menu.item as="button" type="button" wire:click="demote('{{ $document->id }}')" icon="lock-closed">Make Private</flux:menu.item>
                                            @endif

                                            <flux:menu.separator />

                                            <flux:menu.item as="button" type="button" wire:click="delete('{{ $document->id }}')" wire:confirm="Delete this document?" icon="trash" variant="danger">Delete</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
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

    @if ($sharingDocument)
        <section class="rounded-2xl border border-sky-200 bg-sky-50/40 p-5 shadow-sm dark:border-sky-900/70 dark:bg-sky-950/20">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Share {{ $sharingDocument->title }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Create secure links, set optional passwords, and manage active shares.</p>
                </div>

                <flux:button size="sm" variant="ghost" wire:click="closeSharePanel">Close</flux:button>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-[minmax(0,360px)_minmax(0,1fr)]">
                <form wire:submit="createShare" class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:field>
                        <flux:label>Password (optional)</flux:label>
                        <flux:input type="password" wire:model="sharePassword" placeholder="Leave blank for open link" />
                        <flux:error name="sharePassword" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Expires At (optional)</flux:label>
                        <flux:input type="datetime-local" wire:model="shareExpiresAt" />
                        <flux:error name="shareExpiresAt" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Max Downloads (optional)</flux:label>
                        <flux:input type="number" min="1" wire:model="shareMaxDownloads" placeholder="Unlimited" />
                        <flux:error name="shareMaxDownloads" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Access Notes (optional)</flux:label>
                        <flux:textarea rows="3" wire:model="shareAccessNotes" placeholder="Guidance for recipients" />
                        <flux:error name="shareAccessNotes" />
                    </flux:field>

                    <div class="pt-1">
                        <flux:button type="submit" variant="primary">Create Share Link</flux:button>
                    </div>
                </form>

                <div class="space-y-3">
                    @forelse ($sharingDocument->shares as $share)
                        <article wire:key="document-share-{{ $share->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('share.view', $share->share_token) }}" target="_blank" class="block truncate text-sm font-medium text-sky-700 hover:underline dark:text-sky-300">
                                        {{ route('share.view', $share->share_token) }}
                                    </a>

                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 {{ $share->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">{{ $share->is_active ? 'Active' : 'Disabled' }}</span>
                                        @if ($share->share_password)
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">Password</span>
                                        @endif
                                        <span>Downloads: {{ $share->download_count }}{{ $share->max_downloads ? ' / '.$share->max_downloads : '' }}</span>
                                        @if ($share->expires_at)
                                            <span>Expires: {{ $share->expires_at->format('M j, Y g:i A') }}</span>
                                        @endif
                                    </div>

                                    @if ($share->access_notes)
                                        <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-300">{{ $share->access_notes }}</p>
                                    @endif
                                </div>

                                <div class="inline-flex items-center gap-2">
                                    <flux:button size="xs" variant="ghost" wire:click="toggleShare('{{ $share->id }}')">{{ $share->is_active ? 'Disable' : 'Enable' }}</flux:button>
                                    <flux:button size="xs" variant="ghost" wire:click="deleteShare('{{ $share->id }}')" wire:confirm="Delete this share link?" class="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/20">Delete</flux:button>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-zinc-300 bg-white px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                            No share links yet for this document.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif
</div>
