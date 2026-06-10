<div>
    <form wire:submit.prevent="upload">
        <div class="mb-2">
            <label class="block text-sm font-medium mb-1">Title (optional)</label>
            <input wire:model.defer="title" type="text" class="w-full rounded border px-2 py-1" />
        </div>

        <div class="mb-2">
            <input wire:model="file" type="file" />
        </div>

        <div>
            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded">Upload</button>
        </div>
    </form>

    <hr class="my-4" />

    <h3 class="text-lg font-medium mb-2">Project Assets</h3>
    <ul>
        @foreach($assets as $pa)
            <li class="mb-2">
                <a href="{{ Storage::disk($pa->asset->storage_disk)->url($pa->asset->storage_path) }}" target="_blank">{{ $pa->title ?: $pa->asset->original_name }}</a>
                <span class="text-sm text-muted"> — {{ $pa->asset->mime_type }} ({{ $pa->asset->size_bytes }} bytes)</span>
            </li>
        @endforeach
    </ul>
</div>
