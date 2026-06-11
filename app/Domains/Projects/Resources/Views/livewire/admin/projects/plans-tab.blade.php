<div class="space-y-5">
    @php
        $assetRows = $assets->filter(fn ($projectAsset) => $projectAsset->asset !== null)->values();
        $totalAssets = $assetRows->count();
        $totalBytes = (int) $assetRows->sum(fn ($projectAsset) => (int) ($projectAsset->asset?->size_bytes ?? 0));
        $latestAsset = $assetRows->sortByDesc('created_at')->first();
        $formatBytes = function (int $bytes): string {
            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 1).' GB';
            }

            if ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 1).' MB';
            }

            if ($bytes >= 1024) {
                return number_format($bytes / 1024, 1).' KB';
            }

            return number_format($bytes).' B';
        };
    @endphp

    <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <flux:heading size="sm">Upload Plan</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Upload drawing files and plans for this project.</flux:text>
                </div>
            </div>

            <div
                x-data="{
                    selectedFileName: '',
                    lastAutoTitle: '',
                    titleValue: $wire.entangle('title'),
                    isUploading: false,
                    uploadProgress: 0,
                    fileBaseName(fileName) { return fileName ? fileName.replace(/\.[^/.]+$/, '') : '' },
                    syncSelectedFile(fileName) {
                        this.selectedFileName = fileName
                        if (! fileName) { return }
                        const nextTitle = this.fileBaseName(fileName)
                        const currentTitle = (this.titleValue ?? '')
                        if (currentTitle.trim() === '' || this.titleValue === this.lastAutoTitle) {
                            this.titleValue = nextTitle
                            this.lastAutoTitle = nextTitle
                        }
                    }
                }"
                x-on:project-documents-file-input-reset.window="titleValue = ''; selectedFileName = ''; lastAutoTitle = ''; isUploading = false; uploadProgress = 0; $refs.plansFile && ($refs.plansFile.value = null)"
                x-on:livewire-upload-start="isUploading = true; uploadProgress = 0"
                x-on:livewire-upload-finish="isUploading = false; uploadProgress = 100"
                x-on:livewire-upload-error="isUploading = false; uploadProgress = 0"
                x-on:livewire-upload-cancel="isUploading = false; uploadProgress = 0"
                x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
                class="space-y-4"
            >
                <div>
                    <label for="plans-title" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Display title</label>
                    <input
                        id="plans-title"
                        x-model="titleValue"
                        type="text"
                        placeholder="Leave empty to use the original file name"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-zinc-500 focus:ring-2 focus:ring-zinc-200 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100 dark:focus:border-zinc-500 dark:focus:ring-zinc-800"
                    />
                    @error('title')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Plan File</label>
                    <label for="plans-file" class="relative flex cursor-pointer flex-col gap-2 overflow-hidden rounded-lg border border-zinc-300 bg-white px-3 py-3 text-sm shadow-sm transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600" :class="isUploading ? 'pointer-events-none opacity-75' : ''">
                        <div x-show="isUploading" class="absolute inset-y-0 left-0 bg-sky-100/80 transition-[width] duration-200 ease-out dark:bg-sky-900/30" x-bind:style="`width: ${uploadProgress}%`"></div>
                        <div class="relative z-10 flex flex-col gap-1">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">Choose plan file</span>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400" x-text="selectedFileName || 'No file selected yet.'"></span>
                        </div>
                    </label>
                    <input id="plans-file" x-ref="plansFile" type="file" wire:model="assetFile" x-on:change="syncSelectedFile($event.target.files?.[0]?.name ?? ''); console.log('plans-tab: file change', $event.target.files?.[0])" class="sr-only" />
                    @error('assetFile')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:loading.attr="disabled"
                        wire:target="saveAsset,assetFile"
                        x-bind:disabled="!selectedFileName || isUploading"
                        x-on:click="
                            if (!selectedFileName || isUploading) return;
                            const file = $refs.plansFile?.files?.[0];
                            if (! file) { console.warn('No file selected for upload'); return }
                            try {
                                $wire.upload('assetFile', file,
                                    () => { $wire.call('saveAsset') },
                                    (error) => { console.error('plans-tab: upload failed', error) },
                                    (progressEvent) => { uploadProgress = progressEvent?.detail?.progress ?? 0 }
                                )
                            } catch (e) {
                                console.error('plans-tab: upload failed', e)
                            }
                        "
                        class="inline-flex items-center rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300"
                    >
                        Upload plan
                    </button>
                    <span wire:loading wire:target="saveAsset,assetFile" class="text-sm text-zinc-500 dark:text-zinc-400">Uploading...</span>
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-linear-to-b from-white to-zinc-50 p-4 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Total Plans</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($totalAssets) }}</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-linear-to-b from-white to-zinc-50 p-4 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Storage Used</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $formatBytes($totalBytes) }}</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-linear-to-b from-white to-zinc-50 p-4 shadow-sm dark:border-zinc-700 dark:from-zinc-900 dark:to-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Last Upload</p>
                <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $latestAsset?->created_at?->diffForHumans() ?? 'No uploads yet' }}</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <div>
                <flux:heading size="sm">Project Plans</flux:heading>
                <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Drawing files and plans for this project.</flux:text>
            </div>
            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $totalAssets }} {{ Str::plural('file', $totalAssets) }}</span>
        </div>

        @if ($assetRows->isEmpty())
            <div class="px-4 py-12 text-center">
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">No plans uploaded yet.</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Upload your first drawing using the panel above.</p>
            </div>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach ($assetRows as $projectAsset)
                    @php
                        $asset = $projectAsset->asset;
                        $assetUrl = Storage::disk($asset->storage_disk)->url($asset->storage_path);
                        $assetLabel = $projectAsset->title ?: $asset->original_name;
                    @endphp
                    <li wire:key="project-plan-row-{{ $projectAsset->id }}" class="flex flex-col gap-3 px-4 py-4 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0 space-y-1">
                            <a href="{{ $assetUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex max-w-full items-center truncate text-sm font-semibold text-zinc-900 hover:text-zinc-700 dark:text-zinc-100 dark:hover:text-zinc-300">
                                {{ $assetLabel }}
                            </a>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $asset->mime_type ?: 'Unknown type' }} • {{ $formatBytes((int) $asset->size_bytes) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Added {{ $projectAsset->created_at?->diffForHumans() ?? 'recently' }}</p>
                        </div>

                        <div class="flex items-center gap-2 self-start">
                            <a href="{{ $assetUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                                Download
                            </a>
                            <button
                                type="button"
                                x-on:click="if (confirm('Delete this plan and its file? This cannot be undone.')) { $wire.deleteProjectAsset('{{ $projectAsset->id }}') }"
                                wire:loading.attr="disabled"
                                wire:target="deleteProjectAsset"
                                class="inline-flex items-center rounded-md border border-red-200 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-red-900/70 dark:text-red-400 dark:hover:bg-red-950/40"
                            >
                                Delete
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
