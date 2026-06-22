<div class="{{ $embedded ? 'space-y-4' : 'mx-auto w-full max-w-5xl space-y-4 px-4 py-6 sm:px-6 lg:px-8' }}">
    <flux:heading size="{{ $embedded ? 'lg' : 'xl' }}">{{ $submittal ? 'Edit Submittal' : 'Create Submittal' }}</flux:heading>

    <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            @if ($isProjectLocked)
                <flux:field>
                    <flux:label>Project</flux:label>
                    <flux:input :value="$selectedProjectLabel" readonly />
                    <flux:error name="projectId" />
                </flux:field>
            @else
                <flux:field>
                    <flux:label>Project</flux:label>
                    <flux:select wire:model.live="projectId">
                        <option value="">Select project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_number ?? 'N/A' }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="projectId" />
                </flux:field>
            @endif

            <flux:field>
                <flux:label>Package Type</flux:label>
                <flux:input wire:model="type" placeholder="Lighting fixture package, gear package, etc." />
                <flux:error name="type" />
            </flux:field>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <flux:field>
                <flux:label>Spec Reference</flux:label>
                <flux:input wire:model="specReference" placeholder="26 50 00" />
                <flux:error name="specReference" />
            </flux:field>

            <flux:field>
                <flux:label>Vendor/Supplier</flux:label>
                <flux:input wire:model="vendor" placeholder="ABC Lighting" />
                <flux:error name="vendor" />
            </flux:field>

            <flux:field>
                <flux:label>Need-By Date</flux:label>
                <flux:input type="date" wire:model="needByDate" />
                <flux:error name="needByDate" />
            </flux:field>
        </div>

        <div class="space-y-3 rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <flux:heading size="sm">Submittal Items</flux:heading>
                <flux:button type="button" variant="ghost" wire:click="addItem">Add Item</flux:button>
            </div>

            @foreach ($items as $index => $item)
                <div class="grid gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-12 md:items-start" wire:key="submittal-item-{{ $index }}">
                    <div class="min-w-0 md:col-span-4">
                        <flux:input wire:model="items.{{ $index }}.description" placeholder="Description" />
                        <flux:error name="items.{{ $index }}.description" />
                    </div>
                    <div class="min-w-0 md:col-span-2">
                        <flux:input wire:model="items.{{ $index }}.manufacturer" placeholder="Manufacturer" />
                        <flux:error name="items.{{ $index }}.manufacturer" />
                    </div>
                    <div class="min-w-0 md:col-span-2">
                        <flux:input wire:model="items.{{ $index }}.model" placeholder="Model" />
                        <flux:error name="items.{{ $index }}.model" />
                    </div>
                    <div class="min-w-0 md:col-span-2">
                        <flux:input wire:model="items.{{ $index }}.part_number" placeholder="Part #" />
                        <flux:error name="items.{{ $index }}.part_number" />
                    </div>
                    <div class="flex flex-wrap items-start gap-2 md:col-span-2 md:justify-end">
                        <div class="w-full sm:w-24">
                            <flux:input wire:model="items.{{ $index }}.quantity" placeholder="Qty" />
                            <flux:error name="items.{{ $index }}.quantity" />
                        </div>
                        <div class="w-full sm:w-20">
                            <flux:input wire:model="items.{{ $index }}.unit" placeholder="Unit" />
                            <flux:error name="items.{{ $index }}.unit" />
                        </div>
                        <flux:button type="button" variant="danger" wire:click="removeItem({{ $index }})" class="shrink-0">Remove</flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="space-y-2 rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading size="sm">Reviewer Chain</flux:heading>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Select reviewers in sequence. Order is top to bottom.</p>
                <div class="space-y-2">
                    @foreach ($reviewers as $reviewer)
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" value="{{ $reviewer->id }}" wire:model.live="reviewerIds" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950">
                            <span>{{ trim($reviewer->first_name.' '.$reviewer->last_name) }} ({{ $reviewer->email }})</span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="reviewerIds" />
                <flux:error name="reviewerIds.*" />
            </div>

            <div class="space-y-2 rounded-lg border border-zinc-200 p-5 dark:border-zinc-700">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <flux:heading size="sm">Attachments</flux:heading>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Attach existing project documents to this submittal, or upload a new project document first.</p>
                    </div>

                    @if ($canUploadDocument)
                        <flux:button size="sm" variant="ghost" wire:click="openUploadModal" icon="arrow-up-tray">
                            Upload Document
                        </flux:button>
                    @endif
                </div>

                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                    @forelse ($availableDocuments as $document)
                        @php($documentId = (string) $document->id)
                        <div class="space-y-2 rounded-md border border-zinc-200 p-3 dark:border-zinc-700" wire:key="submittal-document-{{ $documentId }}">
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" value="{{ $documentId }}" wire:model.live="documentIds" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950">
                                <span>{{ $document->title ?: $document->original_name }}</span>
                            </label>

                            @if (in_array($documentId, $documentIds, true))
                                <div class="grid gap-2 md:grid-cols-2">
                                    <flux:field>
                                        <flux:label class="text-[11px]">Role</flux:label>
                                        <flux:select wire:model="documentMetadata.{{ $documentId }}.document_role">
                                            <option value="reference">Reference</option>
                                            <option value="primary">Primary</option>
                                            <option value="supporting">Supporting</option>
                                            <option value="compliance">Compliance</option>
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label class="text-[11px]">Status</flux:label>
                                        <flux:select wire:model="documentMetadata.{{ $documentId }}.document_status">
                                            <option value="active">Active</option>
                                            <option value="draft">Draft</option>
                                            <option value="superseded">Superseded</option>
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label class="text-[11px]">Revision</flux:label>
                                        <flux:input wire:model="documentMetadata.{{ $documentId }}.revision" placeholder="Rev A" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label class="text-[11px]">Discipline</flux:label>
                                        <flux:input wire:model="documentMetadata.{{ $documentId }}.discipline" placeholder="Electrical" />
                                    </flux:field>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $projectId !== '' ? 'No project documents are available yet. Use Upload Document to add one.' : 'Select a project to load documents.' }}</p>
                    @endforelse
                </div>
                <flux:error name="documentIds.*" />
                <flux:error name="documentMetadata.*.document_role" />
                <flux:error name="documentMetadata.*.document_status" />
                <flux:error name="documentMetadata.*.revision" />
                <flux:error name="documentMetadata.*.discipline" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ $cancelUrl }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>

    {{-- Inline document upload modal --}}
    <flux:modal wire:model="showUploadModal" class="w-full max-w-xl">
        <div
            x-data="{
                titleValue: $wire.entangle('uploadTitle'),
                selectedFileName: '',
                lastAutoTitle: '',
                isUploading: false,
                uploadProgress: 0,
                fileBaseName(fileName) { return fileName.replace(/\.[^/.]+$/, '') },
                syncSelectedFile(fileName) {
                    this.selectedFileName = fileName
                    if (! fileName) { return }
                    const nextTitle = this.fileBaseName(fileName)
                    if (this.titleValue.trim() === '' || this.titleValue === this.lastAutoTitle) {
                        this.titleValue = nextTitle
                        this.lastAutoTitle = nextTitle
                    }
                }
            }"
            x-on:submittal-upload-reset.window="titleValue = ''; selectedFileName = ''; lastAutoTitle = ''; isUploading = false; uploadProgress = 0; if ($refs.submittalUploadFile) $refs.submittalUploadFile.value = null"
            x-on:livewire-upload-start="isUploading = true; uploadProgress = 0"
            x-on:livewire-upload-finish="isUploading = false; uploadProgress = 100"
            x-on:livewire-upload-error="isUploading = false; uploadProgress = 0"
            x-on:livewire-upload-cancel="isUploading = false; uploadProgress = 0"
            x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
            class="space-y-4"
        >
            <flux:heading size="lg">Upload Document</flux:heading>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Title</label>
                <input type="text" x-model="titleValue" class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" placeholder="Document title" />
                @error('uploadTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">File</label>
                    <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        Max {{ $uploadMaxFileSizeLabel }}
                    </span>
                </div>

                <label
                    for="submittal-upload-file"
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
                        <span x-text="selectedFileName || 'No file selected yet.'" class="text-xs text-zinc-500 dark:text-zinc-400"></span>
                    </div>
                </label>

                <input
                    id="submittal-upload-file"
                    x-ref="submittalUploadFile"
                    type="file"
                    wire:model="uploadFile"
                    accept="{{ $uploadAcceptAttribute }}"
                    x-bind:disabled="isUploading"
                    x-on:change="syncSelectedFile($event.target.files?.[0]?.name ?? '')"
                    class="sr-only"
                />

                <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">Allowed: {{ $uploadAllowedExtensionsLabel }}</p>

                <div wire:loading wire:target="uploadFile" class="mt-2 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-700 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-300">
                    <div class="flex items-center justify-between gap-3">
                        <span>Uploading selection...</span>
                        <span x-text="`${uploadProgress}%`" class="font-semibold"></span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-sky-200/80 dark:bg-sky-950">
                        <div class="h-full rounded-full bg-sky-500 transition-[width] duration-200 ease-out dark:bg-sky-400" x-bind:style="`width: ${uploadProgress}%`"></div>
                    </div>
                </div>

                @error('uploadFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    Description <span class="font-normal normal-case text-zinc-400">(optional)</span>
                </label>
                <textarea wire:model="uploadDescription" rows="2" class="w-full rounded-lg border border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" placeholder="Brief description"></textarea>
                @error('uploadDescription') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                <flux:button type="button" variant="ghost" wire:click="resetUploadModal">Cancel</flux:button>
                <flux:button
                    type="button"
                    variant="primary"
                    wire:click="uploadDocument"
                    wire:loading.attr="disabled"
                    wire:target="uploadDocument,uploadFile"
                >
                    Upload
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
