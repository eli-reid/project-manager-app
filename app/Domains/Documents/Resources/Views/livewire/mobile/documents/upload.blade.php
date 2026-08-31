<div class="flex flex-col gap-6 px-4 py-6 pb-28">
    <div class="rounded-3xl border border-zinc-800 bg-zinc-900 px-4 py-5 shadow-sm">
        <h2 class="mb-1 text-lg font-semibold text-zinc-50">{{ __('Upload Document') }}</h2>
        <p class="mb-4 text-sm text-zinc-400">{{ __('Select a file to upload from your device.') }}</p>

        @if ($success)
            <div class="mb-4 rounded-lg bg-green-700/20 px-3 py-2 text-sm text-green-200">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit.prevent="submitUpload" class="flex flex-col gap-4">
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('File') }}</label>
                <input type="file" wire:model="file" class="block w-full text-sm text-zinc-200 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-zinc-200 hover:file:bg-zinc-700" />
                @error('file')
                    <span class="mt-1 text-xs text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Description') }}</label>
                <input type="text" wire:model="description" maxlength="255" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200" placeholder="{{ __('Optional description...') }}" />
                @error('description')
                    <span class="mt-1 text-xs text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Folder Path') }}</label>
                <input type="text" wire:model="folderPath" maxlength="255" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-zinc-200" placeholder="{{ __('Submittals/Changes/RFI') }}" />
                <p class="mt-1 text-[11px] text-zinc-500">{{ __('Use slashes to create subfolders.') }}</p>
                @error('folderPath')
                    <span class="mt-1 text-xs text-red-400">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="mt-2 rounded-lg bg-sky-800 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 active:bg-sky-900">{{ __('Upload') }}</button>
        </form>

        <a
            href="{{ route('documents.mobile.global') }}"
            class="mt-4 inline-flex min-h-10 items-center justify-center rounded-lg border border-zinc-700 px-4 text-xs font-semibold text-zinc-200 active:bg-zinc-800"
            wire:navigate
            data-mobile-haptic
        >
            {{ __('Back to documents') }}
        </a>
    </div>
</div>
