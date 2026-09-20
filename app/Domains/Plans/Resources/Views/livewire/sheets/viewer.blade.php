<div
    x-data="planSheetViewer({ zoom: @js($zoom), centerX: @js($centerX), centerY: @js($centerY) })"
    x-ref="root"
    x-on:keydown.window="keydown($event)"
    class="flex flex-col bg-zinc-950 text-white"
    :class="fullScreen ? 'fixed inset-0 z-[999] h-screen w-screen' : 'h-[calc(100vh-8rem)]'"
>
    @if (session('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 3000)"
            x-show="show"
            x-transition
            x-cloak
            class="absolute left-1/2 top-16 z-50 -translate-x-1/2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-lg"
        >{{ session('success') }}</div>
    @endif

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-800 bg-zinc-900 px-4 py-2">
        <div class="flex min-w-0 items-center gap-3">
            <flux:button href="{{ route('plans.sheets.index', $project) }}" wire:navigate variant="ghost" icon="arrow-left">Sheets</flux:button>
            <div class="min-w-0">
                <flux:text class="font-semibold text-white">{{ $sheet->sheet_number ?: 'Unnumbered' }}</flux:text>
                <flux:text class="block truncate text-xs text-zinc-400">{{ $sheet->title ?: 'Untitled sheet' }}</flux:text>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1">
            {{-- Tool selection (client-side only — no round trip on tool switch) --}}
            <div class="flex items-center gap-1 rounded-lg bg-zinc-800 p-0.5 text-xs font-medium">
                <button type="button" x-on:click="setTool('pan')" x-bind:class="tool === 'pan' ? 'bg-sky-600 text-white' : 'text-zinc-400 hover:text-white'" class="rounded-md px-2 py-1" title="Pan (V)">Pan</button>
                @if ($this->canAnnotate)
                    <button type="button" x-on:click="setTool('pin')" x-bind:class="tool === 'pin' ? 'bg-sky-600 text-white' : 'text-zinc-400 hover:text-white'" class="rounded-md px-2 py-1" title="Add pin note (P)">Pin</button>
                    <button type="button" x-on:click="setTool('rect')" x-bind:class="tool === 'rect' ? 'bg-sky-600 text-white' : 'text-zinc-400 hover:text-white'" class="rounded-md px-2 py-1" title="Add area note (R)">Area</button>
                @endif
                <button type="button" x-on:click="setTool('capture')" x-bind:class="tool === 'capture' ? 'bg-sky-600 text-white' : 'text-zinc-400 hover:text-white'" class="rounded-md px-2 py-1" title="Copy selection to clipboard (C)">Copy</button>
            </div>

            <div class="flex items-center gap-1">
                <flux:button size="sm" x-on:click="zoomBy(-0.25)" variant="ghost" title="Zoom out (-)">−</flux:button>
                <flux:text class="w-12 text-center text-xs tabular-nums text-zinc-400" x-text="Math.round(zoom * 100) + '%'"></flux:text>
                <flux:button size="sm" x-on:click="zoomBy(0.25)" variant="ghost" title="Zoom in (+)">+</flux:button>
                <flux:button size="sm" x-on:click="fit()" variant="ghost" title="Reset zoom (0)">Fit</flux:button>
            </div>

            <button type="button" x-on:click="showMarkup = !showMarkup; drawMarkers()" x-bind:class="showMarkup ? 'bg-sky-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:text-white'" class="rounded-md px-2 py-1 text-xs font-medium" title="Toggle notes (M)">Notes</button>
            <button type="button" x-on:click="toggleFullScreen()" x-bind:class="fullScreen ? 'bg-sky-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:text-white'" class="rounded-md px-2 py-1 text-xs font-medium" title="Fullscreen (F)">Fullscreen</button>

            @can('compare', $sheet)
                <flux:button size="sm" href="{{ route('plans.sheets.compare', [$project, $sheet]) }}" wire:navigate variant="ghost" icon="arrows-right-left">Compare</flux:button>
            @endcan

            @if ($this->canEditMetadata)
                <flux:button size="sm" wire:click="openMetadataEditor" variant="ghost" icon="pencil-square">Edit details</flux:button>
            @endif
        </div>
    </div>

    <div class="grid flex-1 grid-cols-[4.5rem_minmax(0,1fr)_20rem] overflow-hidden">
        @island(name: 'thumbnail-rail')
            <aside class="h-full overflow-y-auto border-r border-zinc-800 p-2">
                <div class="space-y-2">
                    @foreach ($this->siblings as $sibling)
                        <a wire:key="viewer-sibling-{{ $sibling->id }}" href="{{ route('plans.sheets.show', [$project, $sibling]) }}" wire:navigate class="block rounded-lg p-1 {{ $sibling->is($sheet) ? 'bg-sky-600' : 'bg-zinc-800 hover:bg-zinc-700' }}">
                            <div class="flex aspect-[4/3] items-center justify-center rounded bg-zinc-700 text-[10px] font-semibold">{{ $sibling->sheet_number ?: '?' }}</div>
                        </a>
                    @endforeach
                </div>
            </aside>
        @endisland

        {{-- Sheet stage --}}
        <main
            x-ref="viewport"
            class="relative h-full touch-none select-none overflow-hidden bg-zinc-900"
            :class="cursorClass"
            x-on:wheel="onWheel($event)"
            x-on:pointerdown="onPointerDown($event)"
            x-on:pointermove="onPointerMove($event)"
            x-on:pointerup="onPointerUp($event)"
            x-on:pointercancel="onPointerUp($event)"
            x-on:dblclick="onDoubleClick($event)"
        >
            <div x-ref="stage" class="absolute left-0 top-0" x-bind:style="stageStyle">
                <div class="relative inline-block leading-[0]">
                    @if ($revision->preview_path || $revision->thumbnail_path)
                        <img
                            x-ref="sheetImage"
                            x-on:load="onImageLoad($event)"
                            src="{{ route('plans.images', [$revision, $revision->preview_path ? 'preview' : 'thumb']) }}"
                            alt="{{ $sheet->sheet_number }} {{ $sheet->title }}"
                            class="block max-w-none select-none"
                            draggable="false"
                        >
                        <canvas x-ref="markupCanvas" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                        <canvas x-ref="draftCanvas" class="pointer-events-none absolute inset-0 h-full w-full"></canvas>
                    @else
                        <div class="flex h-96 w-[32rem] items-center justify-center rounded bg-zinc-800 text-zinc-400">Preview unavailable</div>
                    @endif
                </div>
            </div>

            {{-- Existing-note detail popover --}}
            <div
                x-show="activeMarker"
                x-cloak
                x-on:click.outside="activeMarker = null"
                x-bind:style="activeMarker ? `left: ${activeMarker.screenX}px; top: ${activeMarker.screenY}px;` : ''"
                class="fixed z-40 w-72 -translate-x-1/2 rounded-lg border border-zinc-700 bg-zinc-900 p-3 text-sm shadow-2xl"
            >
                <template x-if="activeMarker">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold text-zinc-200" x-text="activeMarker.author"></span>
                            <span class="rounded px-1.5 py-0.5 text-[10px] uppercase tracking-wide" x-bind:class="activeMarker.visibility === 'private' ? 'bg-amber-900/60 text-amber-300' : 'bg-sky-900/60 text-sky-300'" x-text="activeMarker.visibility"></span>
                        </div>
                        <p class="whitespace-pre-line text-zinc-300" x-text="activeMarker.content || '(no note text)'"></p>
                        <div class="flex items-center justify-between gap-2 border-t border-zinc-800 pt-2">
                            <span class="text-xs text-zinc-500" x-text="activeMarker.status === 'resolved' ? 'Resolved' : 'Open'"></span>
                            <div class="flex gap-1" x-show="activeMarker.mine || {{ $this->canManageAnnotations ? 'true' : 'false' }}">
                                <flux:button size="xs" variant="ghost" x-on:click="$wire.toggleAnnotationStatus(activeMarker.id); activeMarker = null" x-text="activeMarker.status === 'resolved' ? 'Reopen' : 'Resolve'"></flux:button>
                                <flux:button size="xs" variant="ghost" wire:confirm="Delete this note?" x-on:click="$wire.deleteAnnotation(activeMarker.id); activeMarker = null">Delete</flux:button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- New-note composer --}}
            <div x-show="draft" x-cloak class="absolute inset-0 z-40 flex items-center justify-center bg-black/50 p-4">
                <div x-on:click.outside="cancelDraft()" class="w-full max-w-sm rounded-xl border border-zinc-700 bg-zinc-900 p-4 shadow-2xl">
                    <template x-if="draft">
                        <div class="space-y-3">
                            <flux:heading size="sm" class="text-white" x-text="draft.type === 'pin' ? 'New pin note' : 'New area note'"></flux:heading>
                            <flux:field>
                                <flux:label>Note</flux:label>
                                <flux:textarea x-model="draft.content" rows="3" placeholder="What should reviewers know?"></flux:textarea>
                            </flux:field>
                            <flux:field>
                                <flux:label>Visibility</flux:label>
                                <flux:select x-model="draft.visibility">
                                    <option value="public">Public — visible to everyone on this project</option>
                                    <option value="private">Private — only you (and managers)</option>
                                </flux:select>
                            </flux:field>
                            <label class="flex items-start gap-2 text-sm text-zinc-300">
                                <input type="checkbox" x-model="draft.pinToRevision" class="mt-0.5 size-4 rounded border-zinc-600 bg-zinc-800 text-sky-600 focus:ring-sky-500">
                                <span>Pin to this revision only (unchecked notes follow every future revision)</span>
                            </label>
                            <div class="flex justify-end gap-2 pt-1">
                                <flux:button variant="ghost" x-on:click="cancelDraft()">Cancel</flux:button>
                                <flux:button variant="primary" x-on:click="saveDraft()">Save note</flux:button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Transient status toast (clipboard capture result) --}}
            <div x-show="captureMessage" x-cloak x-transition class="absolute bottom-4 left-1/2 z-40 -translate-x-1/2 rounded-lg bg-zinc-800 px-4 py-2 text-sm shadow-lg" x-text="captureMessage"></div>

            <div x-show="tool !== 'pan'" x-cloak class="pointer-events-none absolute left-1/2 top-3 -translate-x-1/2 rounded-full bg-zinc-800/90 px-3 py-1 text-xs text-zinc-300 shadow">
                <span x-show="tool === 'pin'">Click the sheet to place a pin note.</span>
                <span x-show="tool === 'rect'">Drag to draw an area note.</span>
                <span x-show="tool === 'capture'">Drag to select an area to copy as an image.</span>
            </div>
        </main>

        {{-- Right panel --}}
        <aside class="flex h-full flex-col overflow-hidden border-l border-zinc-800 bg-zinc-900">
            <div class="flex border-b border-zinc-800">
                @foreach (['details' => 'Details', 'revisions' => 'Revisions', 'notes' => 'Notes'] as $key => $label)
                    <button
                        type="button"
                        x-on:click="panel = '{{ $key }}'"
                        x-bind:class="panel === '{{ $key }}' ? 'border-sky-500 text-white' : 'border-transparent text-zinc-500 hover:text-zinc-300'"
                        class="flex-1 border-b-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide transition-colors"
                    >{{ $label }}</button>
                @endforeach
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                {{-- Details --}}
                <div x-show="panel === 'details'" x-cloak>
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-zinc-500">Sheet</dt><dd>{{ $sheet->sheet_number ?: 'Unnumbered' }}</dd></div>
                        <div><dt class="text-zinc-500">Title</dt><dd>{{ $sheet->title ?: 'Untitled sheet' }}</dd></div>
                        <div><dt class="text-zinc-500">Discipline</dt><dd>{{ $sheet->discipline ?: 'Unassigned' }}</dd></div>
                        <div><dt class="text-zinc-500">Page</dt><dd>{{ $revision->page_number ?? '—' }}</dd></div>
                        <div><dt class="text-zinc-500">Revision</dt><dd>{{ $revision->revision_label ?: 'Current' }}</dd></div>
                        <div>
                            <dt class="text-zinc-500">Plan date</dt>
                            <dd>{{ $revision->set?->issued_at?->format('M j, Y') ?? 'Not set' }}</dd>
                        </div>
                        <div><dt class="text-zinc-500">Uploaded</dt><dd>{{ $revision->created_at->format('M j, Y') }}</dd></div>
                    </dl>
                </div>

                {{-- Revisions --}}
                <div x-show="panel === 'revisions'" x-cloak class="space-y-2">
                    @foreach ($this->orderedRevisions as $sheetRevision)
                        <div wire:key="viewer-revision-{{ $sheetRevision->id }}" class="rounded-lg {{ $sheetRevision->id === $revision->id ? 'bg-sky-600' : 'bg-zinc-800' }}">
                            <button wire:click="selectRevision('{{ $sheetRevision->id }}')" class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:opacity-90">
                                <span>
                                    <span class="block font-medium">{{ $sheetRevision->revision_label ?: 'Revision' }}</span>
                                    <span class="block text-xs opacity-75">{{ $sheetRevision->effectiveDate()->format('M j, Y') }}</span>
                                </span>
                                @if ($sheetRevision->is_current)
                                    <span class="shrink-0 rounded bg-black/20 px-1.5 py-0.5 text-[10px] uppercase tracking-wide">Current</span>
                                @endif
                            </button>
                            @can('publishRevision', $sheet)
                                @if (! $sheetRevision->is_current)
                                    <div class="border-t border-black/20 px-3 py-1.5">
                                        <button wire:click="publishRevision('{{ $sheetRevision->id }}')" wire:confirm="Publish this revision as the current one for this sheet?" class="text-xs text-zinc-300 underline decoration-dotted hover:text-white">Set as current</button>
                                    </div>
                                @endif
                            @endcan
                        </div>
                    @endforeach
                </div>

                {{-- Notes --}}
                <div x-show="panel === 'notes'" x-cloak class="space-y-4">
                    <div class="flex gap-1 rounded-lg bg-zinc-800 p-0.5 text-xs">
                        <button wire:click="setNotesScope('current')" class="flex-1 rounded-md px-2 py-1 {{ $notesScope === 'current' ? 'bg-sky-600 text-white' : 'text-zinc-400 hover:text-white' }}">This revision</button>
                        <button wire:click="setNotesScope('all')" class="flex-1 rounded-md px-2 py-1 {{ $notesScope === 'all' ? 'bg-sky-600 text-white' : 'text-zinc-400 hover:text-white' }}">All revisions</button>
                    </div>

                    <div class="space-y-2 text-xs">
                        <label class="flex items-center gap-2 text-zinc-300"><flux:checkbox wire:model.live="showPublicNotes" />Public notes</label>
                        <label class="flex items-center gap-2 text-zinc-300"><flux:checkbox wire:model.live="showPrivateNotes" />Private notes</label>
                        <label class="flex items-center gap-2 text-zinc-300"><flux:checkbox wire:model.live="showResolvedNotes" />Resolved notes</label>
                    </div>

                    <div class="space-y-2">
                        @forelse ($this->currentRevisionAnnotations as $annotation)
                            <div wire:key="note-{{ $annotation->id }}" x-data="{ editing: false, content: @js($annotation->content), visibility: @js($annotation->visibility) }" class="rounded-lg border border-zinc-800 bg-zinc-950 p-3 text-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-zinc-200">{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                    <span class="rounded px-1.5 py-0.5 text-[10px] uppercase tracking-wide {{ $annotation->visibility === 'private' ? 'bg-amber-900/60 text-amber-300' : 'bg-sky-900/60 text-sky-300' }}">{{ $annotation->visibility }}</span>
                                </div>

                                <template x-if="!editing">
                                    <p class="mt-1 whitespace-pre-line text-zinc-400">{{ $annotation->content ?: '(no note text)' }}</p>
                                </template>
                                <template x-if="editing">
                                    <div class="mt-2 space-y-2">
                                        <flux:textarea x-model="content" rows="3" />
                                        <flux:select x-model="visibility">
                                            <option value="public">Public</option>
                                            <option value="private">Private</option>
                                        </flux:select>
                                        <div class="flex justify-end gap-2">
                                            <flux:button size="xs" variant="ghost" x-on:click="editing = false">Cancel</flux:button>
                                            <flux:button size="xs" variant="primary" x-on:click="$wire.updateAnnotation('{{ $annotation->id }}', content, visibility); editing = false">Save</flux:button>
                                        </div>
                                    </div>
                                </template>

                                <div class="mt-2 flex items-center justify-between text-xs text-zinc-500">
                                    <span>{{ $annotation->status === 'resolved' ? 'Resolved' : 'Open' }} · {{ $annotation->created_at->diffForHumans() }}</span>
                                    @if ($annotation->author_id === auth()->id() || $this->canManageAnnotations)
                                        <div class="flex gap-2">
                                            <button type="button" x-on:click="editing = !editing" class="hover:text-white">Edit</button>
                                            <button type="button" wire:click="toggleAnnotationStatus('{{ $annotation->id }}')" class="hover:text-white">{{ $annotation->status === 'resolved' ? 'Reopen' : 'Resolve' }}</button>
                                            <button type="button" wire:click="deleteAnnotation('{{ $annotation->id }}')" wire:confirm="Delete this note?" class="text-red-400 hover:text-red-300">Delete</button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <flux:text class="text-sm text-zinc-500">No notes on this revision yet.</flux:text>
                        @endforelse
                    </div>

                    @if ($notesScope === 'all' && $this->historicalAnnotations->isNotEmpty())
                        <div>
                            <flux:heading size="xs" class="uppercase tracking-wide text-zinc-500">Notes on other revisions</flux:heading>
                            <div class="mt-2 space-y-2">
                                @foreach ($this->historicalAnnotations as $annotation)
                                    <div wire:key="historical-note-{{ $annotation->id }}" class="rounded-lg border border-zinc-800 bg-zinc-950/60 p-3 text-sm opacity-80">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-medium text-zinc-300">{{ $annotation->author?->name ?? 'Unknown' }}</span>
                                            <span class="text-[10px] uppercase tracking-wide text-zinc-500">{{ $annotation->revision?->revision_label ?: 'Revision' }} · {{ $annotation->revision?->effectiveDate()->format('M j, Y') }}</span>
                                        </div>
                                        <p class="mt-1 whitespace-pre-line text-zinc-500">{{ $annotation->content ?: '(no note text)' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </aside>
    </div>

    {{-- Metadata editor --}}
    @if ($this->canEditMetadata)
        <flux:modal wire:model="editingMetadata" class="w-full max-w-lg">
            <form wire:submit="saveMetadata" class="space-y-4">
                <flux:heading size="lg">Edit sheet details</flux:heading>
                <flux:field>
                    <flux:label>Sheet number</flux:label>
                    <flux:input wire:model="metaSheetNumber" />
                    <flux:error name="metaSheetNumber" />
                </flux:field>
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input wire:model="metaTitle" />
                    <flux:error name="metaTitle" />
                </flux:field>
                <flux:field>
                    <flux:label>Discipline</flux:label>
                    <flux:input wire:model="metaDiscipline" />
                    <flux:error name="metaDiscipline" />
                </flux:field>
                <div class="flex justify-end gap-2 border-t border-zinc-800 pt-4">
                    <flux:button type="button" variant="ghost" wire:click="$set('editingMetadata', false)">Cancel</flux:button>
                    <flux:button type="submit" variant="primary">Save</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Jump-to-sheet palette --}}
    <div x-show="palette" x-cloak class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-8" x-on:click.self="palette = false">
        <div class="w-full max-w-xl rounded-xl border border-zinc-700 bg-zinc-900 p-4 shadow-2xl">
            <flux:input x-ref="jumpSearch" x-init="$watch('palette', value => value && $nextTick(() => $refs.jumpSearch.focus()))" placeholder="Jump to sheet number or title..." />
            <div class="mt-3 max-h-80 space-y-1 overflow-y-auto">
                @foreach ($this->siblings as $sibling)
                    <a wire:key="palette-sheet-{{ $sibling->id }}" href="{{ route('plans.sheets.show', [$project, $sibling]) }}" wire:navigate x-on:click="palette = false" class="block rounded-lg px-3 py-2 hover:bg-zinc-800">
                        <span class="font-semibold">{{ $sibling->sheet_number ?: '?' }}</span>
                        <span class="ml-2 text-zinc-400">{{ $sibling->title }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
