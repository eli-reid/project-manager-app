<div>
    <form wire:submit.prevent="upload">
        <div class="mb-2">
            <label class="block text-sm font-medium mb-1">Title (optional)</label>
            <input wire:model.defer="title" type="text" class="w-full rounded border px-2 py-1" />
            @error('title') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <input wire:model="file" type="file" />
            @error('file') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded">Upload</button>
            <div wire:loading wire:target="file" class="text-sm text-gray-600">Uploading...</div>
        </div>
    </form>

    <hr class="my-4" />

    <h3 class="text-lg font-medium mb-2">Project Assets</h3>
    <ul>
        @foreach($assets as $pa)
            <li class="mb-3 flex items-start justify-between">
                <div>
                    <a href="{{ Storage::disk($pa->asset->storage_disk)->url($pa->asset->storage_path) }}" target="_blank" class="font-medium">{{ $pa->title ?: $pa->asset->original_name }}</a>
                    <div class="text-sm text-gray-600">{{ $pa->asset->mime_type }} • {{ number_format($pa->asset->size_bytes) }} bytes</div>
                    @if($pa->created_by_id)
                        <div class="text-xs text-gray-500">Added {{ $pa->created_at->diffForHumans() }}</div>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ Storage::disk($pa->asset->storage_disk)->url($pa->asset->storage_path) }}" target="_blank" class="text-sm text-blue-600">Download</a>
                    <button type="button" class="text-sm text-red-600" onclick="if(confirm('Delete this asset and its file? This cannot be undone.')) { Livewire.emit('deleteProjectAsset', '{{ $pa->id }}') }">Delete</button>
                </div>
            </li>
        @endforeach
    </ul>
</div>
