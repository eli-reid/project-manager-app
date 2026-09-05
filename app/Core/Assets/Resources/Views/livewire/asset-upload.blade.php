<div x-data="{
    selectedFileName: '',
    isUploading: false,
    progress: 0,
    syncSelected(file) {
        this.selectedFileName = file?.name ?? '';
    }
}"
    x-on:assets-file-input-reset.window="selectedFileName = ''; isUploading = false; progress = 0; $refs.coreAssetFile.value = null"
    x-on:livewire-upload-start="isUploading = true; progress = 0"
    x-on:livewire-upload-finish="isUploading = false; progress = 100"
    x-on:livewire-upload-error="isUploading = false; progress = 0"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
    class="space-y-3"
>
    <flux:field>
        <flux:label>Title (optional)</flux:label>
        <flux:input wire:model.defer="title" placeholder="Leave empty to use original file name" />
        <flux:error name="title" />
    </flux:field>

    <flux:field>
        <flux:label>File</flux:label>

        <label class="relative flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2" :class="isUploading ? 'opacity-75 pointer-events-none' : ''">
            <svg class="h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5m0 0l5 5M12 5v12"/></svg>
            <div>
                <div class="text-sm font-medium text-zinc-900">Choose file</div>
                <div class="text-xs text-zinc-500" x-text="selectedFileName || 'No file selected yet.'"></div>
            </div>
            <input x-ref="coreAssetFile" type="file" wire:model="assetFile" class="sr-only" x-on:change="syncSelected($event.target.files?.[0])" />
        </label>

        <flux:error name="assetFile" />

        <div wire:loading wire:target="assetFile" class="mt-2 rounded bg-sky-50 px-3 py-2 text-xs text-sky-700">
            Uploading… <span x-text="progress + '%'" class="font-semibold ml-2"></span>
        </div>
    </flux:field>

    <div class="flex items-center gap-3">
        <flux:button variant="primary" wire:click="saveAsset" wire:loading.attr="disabled" wire:target="assetFile">
            Upload
        </flux:button>
        <flux:button variant="ghost" type="button" x-show="selectedFileName === ''" @click="$refs.coreAssetFile.click()">Choose File</flux:button>
    </div>
</div>
