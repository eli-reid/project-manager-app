<div class="space-y-4">
    <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Project Documents</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage project-owned documents directly from this tab.</p>
        </div>

        <div class="w-full lg:w-72">
            <input type="text" wire:model.live="search" placeholder="Search documents..." class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
        </div>
    </div>

    <div
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
        x-on:project-documents-file-input-reset.window="titleValue = ''; selectedFileName = ''; lastAutoTitle = ''; isUploading = false; uploadProgress = 0; $refs.projectDocumentFile.value = null"
        x-on:livewire-upload-start="isUploading = true; uploadProgress = 0"
        x-on:livewire-upload-finish="isUploading = false; uploadProgress = 100"
        x-on:livewire-upload-error="isUploading = false; uploadProgress = 0"
        x-on:livewire-upload-cancel="isUploading = false; uploadProgress = 0"
        x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
        class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
    >
        <div class="grid gap-3 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</label>
                <input type="text" x-model="titleValue" class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Folder Path</label>
                <input type="text" wire:model="folderPath" placeholder="Submittals/Changes/RFI" class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">Use slashes to create subfolders.</p>
                @error('folderPath') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">File</label>
                    <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        Max {{ $maxFileSizeLabel }}
                    </span>
                </div>
                <div class="space-y-2">
                    @php($defaultFileLabel = optional($file)->getClientOriginalName() ?? ($editingDocumentId ? 'No new file selected. The current file will be kept.' : 'No file selected yet.'))

                    <label
                        for="project-document-file"
                        x-bind:class="isUploading ? 'pointer-events-none opacity-75' : ''"
                        class="relative flex cursor-pointer flex-col gap-2 overflow-hidden rounded-lg border border-zinc-300 bg-white px-3 py-3 text-sm shadow-sm transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
                    >
                        <div
                            x-show="isUploading"
                            class="absolute inset-y-0 left-0 bg-sky-100/80 transition-[width] duration-200 ease-out dark:bg-sky-900/30"
                            x-bind:style="`width: ${uploadProgress}%`"
                        ></div>

                        <div class="relative z-10 flex flex-col gap-1">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Choose file</span>
                            <span x-text="selectedFileName || @js($defaultFileLabel)" class="text-xs text-zinc-500 dark:text-zinc-400"></span>
                        </div>
                    </label>

                    <input id="project-document-file" x-ref="projectDocumentFile" type="file" wire:model="file" accept="{{ $acceptAttribute }}" x-bind:disabled="isUploading" x-on:change="syncSelectedFile($event.target.files?.[0]?.name ?? '')" class="sr-only" />

                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Allowed: {{ $allowedExtensionsLabel }}</p>

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
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-3">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Description</label>
            <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"></textarea>
            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,file" class="rounded-md bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                {{ $editingDocumentId ? 'Update Document' : 'Save Document' }}
            </button>

            @if ($editingDocumentId)
                <button type="button" wire:click="cancelEdit" wire:loading.attr="disabled" wire:target="save,file" class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Cancel
                </button>
            @endif
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Folder</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">File</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Uploaded By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($documents as $document)
                        <tr wire:key="project-document-{{ $document->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $document->title }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ $document->folder_path ?: 'Unsorted' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">
                                <x-ui.pdf-viewer :document="$document" />
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300">{{ trim(($document->uploadedBy?->first_name ?? '').' '.($document->uploadedBy?->last_name ?? '')) ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $document->created_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button size="xs" variant="ghost" icon-trailing="ellipsis-horizontal"></flux:button>

                                    <flux:menu>
                                        <flux:menu.item :href="route('documents.download', $document)" icon="arrow-down-tray">Download</flux:menu.item>
                                        <flux:menu.item as="button" type="button" wire:click="edit('{{ $document->id }}')" icon="pencil-square">Edit</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item as="button" type="button" wire:click="delete('{{ $document->id }}')" wire:confirm="Delete this document?" icon="trash" variant="danger">Delete</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No project documents yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
