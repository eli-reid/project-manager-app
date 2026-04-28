{{--
    PDF Viewer Component
    Usage: <x-ui.pdf-viewer :document="$document" />

    Props:
      - $document: App\Domains\Documents\Models\Document  (required)

    Renders a clickable document name. If the document is a PDF, clicking opens
    a full-screen overlay with an embedded PDF viewer and a download fallback.
    Non-PDF documents render as plain text with a download link only.
--}}
@props(['document'])

@php
    $isPdf = strtolower($document->extension ?? '') === 'pdf'
        || $document->mime_type === 'application/pdf';

    $displayName = $document->title ?: $document->original_name;

    $viewUrl     = $isPdf ? route('documents.view', $document)     : null;
    $downloadUrl = route('documents.download', $document);
@endphp

@if ($isPdf)
    <div
        x-data="{ open: false }"
        class="flex items-center justify-between gap-3 py-1"
    >
        {{-- Clickable name opens the viewer --}}
        <button
            type="button"
            @click="open = true"
            class="inline-flex items-center gap-2 text-sm font-medium text-zinc-900 hover:text-indigo-600 dark:text-zinc-100 dark:hover:text-indigo-400"
        >
            <flux:icon.document-text class="size-4 shrink-0 text-red-500" />
            <span>{{ $displayName }}</span>
        </button>

        {{-- Separate download link --}}
        <a
            href="{{ $downloadUrl }}"
            class="shrink-0 text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
            title="Download"
        >
            <flux:icon.arrow-down-tray class="size-4" />
        </a>

        {{-- Overlay viewer --}}
        <template x-teleport="body">
            <div
                x-show="open"
                x-transition.opacity
                x-cloak
                class="fixed inset-0 z-50 flex flex-col bg-zinc-950/90"
                @keydown.escape.window="open = false"
            >
                {{-- Toolbar --}}
                <div class="flex shrink-0 items-center justify-between border-b border-zinc-700 bg-zinc-900 px-4 py-3">
                    <div class="flex items-center gap-2 text-sm font-medium text-zinc-100">
                        <flux:icon.document-text class="size-4 text-red-400" />
                        {{ $displayName }}
                    </div>
                    <div class="flex items-center gap-3">
                        <a
                            href="{{ $downloadUrl }}"
                            class="inline-flex items-center gap-1.5 rounded-md border border-zinc-600 px-3 py-1.5 text-xs font-medium text-zinc-300 hover:bg-zinc-700"
                        >
                            <flux:icon.arrow-down-tray class="size-3.5" />
                            Download
                        </a>
                        <button
                            type="button"
                            @click="open = false"
                            class="rounded-md p-1.5 text-zinc-400 hover:bg-zinc-700 hover:text-zinc-100"
                            aria-label="Close"
                        >
                            <flux:icon.x-mark class="size-5" />
                        </button>
                    </div>
                </div>

                {{-- PDF iframe --}}
                <iframe
                    src="{{ $viewUrl }}"
                    class="h-full w-full flex-1 border-0 bg-white"
                    title="{{ $displayName }}"
                ></iframe>
            </div>
        </template>
    </div>
@else
    {{-- Non-PDF: show name + download icon --}}
    <div class="flex items-center justify-between gap-3 py-1">
        <div class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
            <flux:icon.document class="size-4 shrink-0 text-zinc-400" />
            <span>{{ $displayName }}</span>
        </div>
        <a
            href="{{ $downloadUrl }}"
            class="shrink-0 text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
            title="Download"
        >
            <flux:icon.arrow-down-tray class="size-4" />
        </a>
    </div>
@endif
