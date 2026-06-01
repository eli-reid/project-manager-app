<div class="mx-auto max-w-5xl space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isEditing ? 'Edit Change Order' : 'Create Change Order' }}</h1>
        <a href="{{ route('admin.change-orders.index') }}" class="rounded-md border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">Back</a>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Project <span class="text-red-500">*</span></label>
                <select wire:model.live="projectId" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">Select project</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_number ?? 'N/A' }})</option>
                    @endforeach
                </select>
                @error('projectId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Title <span class="text-red-500">*</span></label>
                <input type="text" wire:model="title" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Description</label>
                <textarea wire:model="description" rows="4" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Labor Amount</label>
                <input type="number" step="0.01" min="0" wire:model="laborAmount" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                @error('laborAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Materials Amount</label>
                <input type="number" step="0.01" min="0" wire:model="materialsAmount" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                @error('materialsAmount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Notes</label>
                <textarea wire:model="notes" rows="3" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                @error('notes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Attach Project Documents</h2>

        @if ($projectId === '')
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Select a project to see attachable documents.</p>
        @elseif ($availableDocuments->isEmpty())
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">No project documents are currently available.</p>
        @else
            <div class="mt-3 space-y-2">
                @foreach ($availableDocuments as $document)
                    @php
                        $documentId = (string) $document->id;
                    @endphp

                    <div class="rounded-md border border-zinc-200 p-2 dark:border-zinc-700">
                        <label class="flex items-start gap-2">
                            <input type="checkbox" value="{{ $documentId }}" wire:model.live="documentIds" class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-950">
                            <span class="text-sm text-zinc-800 dark:text-zinc-200">{{ $document->title }}</span>
                        </label>

                        @if (in_array($documentId, $documentIds, true))
                            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300">Role</label>
                                    <select wire:model.live="documentMetadata.{{ $documentId }}.document_role" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                        @foreach (\App\Domains\ChangeOrders\Models\ChangeOrder::allowedDocumentRoles() as $role)
                                            <option value="{{ $role }}">{{ ucfirst($role) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300">Status</label>
                                    <select wire:model.live="documentMetadata.{{ $documentId }}.document_status" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                        @foreach (\App\Domains\ChangeOrders\Models\ChangeOrder::allowedDocumentStatuses() as $status)
                                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300">Revision</label>
                                    <input type="text" wire:model.live="documentMetadata.{{ $documentId }}.revision" maxlength="40" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" placeholder="Rev A">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300">Discipline</label>
                                    <input type="text" wire:model.live="documentMetadata.{{ $documentId }}.discipline" maxlength="60" class="mt-1 block w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" placeholder="Structural">
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex justify-end">
        <button wire:click="save" wire:loading.attr="disabled" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
            {{ $isEditing ? 'Update Change Order' : 'Create Change Order' }}
        </button>
    </div>
</div>
