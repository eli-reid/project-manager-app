<div class="flex flex-col gap-6 px-4 py-6 pb-28">
    <div class="rounded-3xl border border-zinc-800 bg-zinc-900 px-4 py-5 shadow-sm">
        <h2 class="text-lg font-semibold text-zinc-50 mb-1">{{ __('Upload Document') }}</h2>
        <p class="text-sm text-zinc-400 mb-4">{{ __('Select a file to upload from your device. Supported formats: PDF, images, Office docs, text.') }}</p>

        @if ($success)
            <div class="mb-4 rounded-lg bg-green-700/20 px-3 py-2 text-green-200 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="upload" class="flex flex-col gap-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-400 mb-2">{{ __('File') }}</label>
                <input type="file" wire:model="file" class="block w-full text-sm text-zinc-200 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-zinc-200 hover:file:bg-zinc-700" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt" />
                @error('file') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-400 mb-2">{{ __('Description') }}</label>
                <input type="text" wire:model="description" maxlength="255" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200" placeholder="{{ __('Optional description...') }}" />
                @error('description') <span class="text-xs text-red-400 mt-1">{{ $message }}</span> @enderror
            </div>
            <button type="submit" class="mt-2 rounded-lg bg-sky-800 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 active:bg-sky-900">{{ __('Upload') }}</button>
        </form>
    </div>
</div>
