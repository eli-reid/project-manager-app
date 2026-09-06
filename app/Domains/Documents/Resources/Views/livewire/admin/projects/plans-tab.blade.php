<div class="space-y-4">
    {{-- Header row: title, search, upload toggle --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Plans &amp; Blueprints</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Drawing sets and blueprints for this project.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="w-60">
                <flux:input type="text" wire:model.live.debounce.300ms="search" placeholder="Search plans..." />
            </div>
            @if ($canManageProjectPlans)
                <flux:button
                    variant="primary"
                    size="sm"
                    x-data
                    x-on:click="$dispatch('toggle-plan-upload-panel')"
                >
                    {{ $editingDocumentId ? 'Editing Plan' : '+ Upload Plan' }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Set filter chips --}}
    @if ($sets->count() > 0)
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                wire:click="setActiveSet('')"
                class="rounded-full border px-3 py-1 text-xs font-medium transition {{ $activeSet === '' ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' : 'border-zinc-300 text-zinc-600 hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
            >All Sets</button>
            @foreach ($sets as $set)
                <button
                    type="button"
                    wire:click="setActiveSet('{{ $set }}')"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition {{ $activeSet === $set ? 'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' : 'border-zinc-300 text-zinc-600 hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                >{{ $set }}</button>
            @endforeach
        </div>
    @endif

    {{-- Upload / Edit panel (collapsible) --}}
    @if ($canManageProjectPlans)
        <div
            x-data="{
                open: @js($editingDocumentId !== null),
                titleValue: $wire.entangle('title'),
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
            x-on:toggle-plan-upload-panel.window="open = !open"
            x-on:project-plans-file-input-reset.window="titleValue = ''; selectedFileName = ''; lastAutoTitle = ''; isUploading = false; uploadProgress = 0; open = false; $refs.projectPlanFile && ($refs.projectPlanFile.value = null)"
            x-on:livewire-upload-start="isUploading = true; uploadProgress = 0"
            x-on:livewire-upload-finish="isUploading = false; uploadProgress = 100"
            x-on:livewire-upload-error="isUploading = false; uploadProgress = 0"
            x-on:livewire-upload-cancel="isUploading = false; uploadProgress = 0"
            x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
        >
            <div x-show="open" x-collapse class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h3 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                    {{ $editingDocumentId ? 'Edit Plan' : 'Upload Plan' }}
                </h3>

                <div class="grid gap-3 md:grid-cols-2">
                    <flux:field>
                        <flux:label>Title</flux:label>
                        <input type="text" x-model="titleValue" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                        <flux:error name="title" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Drawing Set</flux:label>
                        <flux:input type="text" wire:model="planSet" placeholder="Architectural, Structural, MEP..." />
                        <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">Group blueprints by discipline or set name.</p>
                        <flux:error name="planSet" />
                    </flux:field>

                    <div class="md:col-span-2">
                        <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">File</label>
                            <span class="inline-flex items-center rounded-full border border-zinc-200 bg-zinc-50 px-2 py-0.5 text-[11px] font-medium text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                Max {{ $maxFileSizeLabel }}
                            </span>
                        </div>
                        <div class="space-y-2">
                            @php
                                $defaultFileLabel = optional($file)->getClientOriginalName() ?? ($editingDocumentId ? 'No new file — current file will be kept.' : 'No file selected yet.');
                            @endphp
                            <label
                                for="project-plan-file"
                                x-bind:class="isUploading ? 'pointer-events-none opacity-75' : ''"
                                class="relative flex cursor-pointer flex-col gap-2 overflow-hidden rounded-lg border border-zinc-300 bg-white px-3 py-3 text-sm shadow-sm transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
                            >
                                <div x-show="isUploading" class="absolute inset-y-0 left-0 bg-sky-100/80 transition-[width] duration-200 ease-out dark:bg-sky-900/30" x-bind:style="`width: ${uploadProgress}%`"></div>
                                <div class="relative z-10 flex flex-col gap-1">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">Choose file</span>
                                    <span x-text="selectedFileName || @js($defaultFileLabel)" class="text-xs text-zinc-500 dark:text-zinc-400"></span>
                                </div>
                            </label>
                            <input id="project-plan-file" x-ref="projectPlanFile" type="file" wire:model="file" accept="{{ $acceptAttribute }}" x-bind:disabled="isUploading" x-on:change="syncSelectedFile($event.target.files?.[0]?.name ?? '')" class="sr-only" />
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
                        <flux:error name="file" />
                    </div>
                </div>

                <div class="mt-3">
                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" rows="3" />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled" wire:target="save,file">
                        {{ $editingDocumentId ? 'Update Plan' : 'Save Plan' }}
                    </flux:button>
                    @if ($editingDocumentId)
                        <flux:button variant="ghost" size="sm" wire:click="cancelEdit" wire:loading.attr="disabled" wire:target="save,file">
                            Cancel
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Plans grid --}}
    @if ($plans->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white py-12 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                @if ($search !== '')
                    No plans match your search.
                @elseif ($activeSet !== '')
                    No plans in this set.
                @else
                    No blueprints uploaded for this project yet.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($plans as $plan)
                @php
                    $planExt = strtolower($plan->extension ?? pathinfo($plan->original_name ?? '', PATHINFO_EXTENSION) ?? '');
                    $isImage = in_array($planExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                    $isPdf = $planExt === 'pdf' || $plan->mime_type === 'application/pdf';
                @endphp
                <div wire:key="project-plan-{{ $plan->id }}" class="group overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                        @if ($isImage)
                            <img src="{{ route('documents.view', $plan) }}" alt="{{ $plan->title }}" class="h-full w-full object-cover" loading="lazy" />
                        @elseif ($isPdf)
                            <flux:icon.document-text class="size-12 text-red-400" />
                        @else
                            <flux:icon.document class="size-12 text-zinc-400" />
                        @endif

                        <a
                            href="{{ $isPdf || $isImage ? route('documents.view', $plan) : route('documents.download', $plan) }}"
                            target="_blank"
                            rel="noopener"
                            class="absolute inset-0 flex items-center justify-center bg-zinc-950/0 opacity-0 transition group-hover:bg-zinc-950/40 group-hover:opacity-100"
                        >
                            <span class="rounded-md bg-white/90 px-3 py-1.5 text-xs font-semibold text-zinc-900">View</span>
                        </a>
                    </div>

                    <div class="p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100" title="{{ $plan->title }}">{{ $plan->title }}</p>
                                @if ($plan->folder_path && $plan->folder_path !== \App\Domains\Documents\Livewire\Admin\Projects\PlansTab::PLANS_FOLDER)
                                    <p class="mt-0.5 truncate text-[11px] text-zinc-400 dark:text-zinc-500">{{ Str::after($plan->folder_path, \App\Domains\Documents\Livewire\Admin\Projects\PlansTab::PLANS_FOLDER.'/') }}</p>
                                @endif
                            </div>
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="xs" variant="ghost" icon-trailing="ellipsis-horizontal"></flux:button>
                                <flux:menu>
                                    <flux:menu.item :href="route('documents.download', $plan)" icon="arrow-down-tray">Download</flux:menu.item>
                                    @if ($canUpdateProjectPlans)
                                        <flux:menu.item as="button" type="button" wire:click="edit('{{ $plan->id }}')" icon="pencil-square">Edit</flux:menu.item>
                                    @endif
                                    @if ($canDeleteProjectPlans)
                                        <flux:menu.separator />
                                        <flux:menu.item as="button" type="button" wire:click="delete('{{ $plan->id }}')" wire:confirm="Delete this plan?" icon="trash" variant="danger">Delete</flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                        <p class="mt-1 text-[11px] text-zinc-400 dark:text-zinc-500">
                            {{ $plan->created_at?->format('M j, Y') ?? '—' }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
