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

                    @if ($uploadDocumentUrl)
                        <flux:button size="sm" variant="ghost" :href="$uploadDocumentUrl" target="_blank" icon="arrow-up-tray">
                            Upload Document
                        </flux:button>
                    @endif
                </div>

                @if ($uploadDocumentUrl)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">The upload form opens in the project documents tab in a new tab so you can return here and select the file after it saves.</p>
                @endif

                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                    @forelse ($availableDocuments as $document)
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" value="{{ $document->id }}" wire:model.live="documentIds" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950">
                            <span>{{ $document->title ?: $document->original_name }}</span>
                        </label>
                    @empty
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $projectId !== '' ? 'No project documents are available yet. Use Upload Document to add one.' : 'Select a project to load documents.' }}</p>
                    @endforelse
                </div>
                <flux:error name="documentIds.*" />
            </div>
        </div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ $cancelUrl }}" wire:navigate class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
