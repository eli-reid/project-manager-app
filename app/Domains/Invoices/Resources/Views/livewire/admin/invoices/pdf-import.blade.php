<div class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Import Invoices from PDF</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Upload one or more invoice PDFs to extract and batch-import invoice data.</p>
        </div>
        <a href="{{ route('admin.invoices.index') }}" wire:navigate class="rounded-md border border-zinc-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Back</a>
    </div>

    @if (! $uploaded)
        {{-- Upload step --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <form wire:submit="startImport" class="space-y-4">
                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project <span class="text-red-500">*</span></label>
                    <select wire:model="project_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                        <option value="">Select a project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}{{ $project->project_number ? ' ('.$project->project_number.')' : '' }}</option>
                        @endforeach
                    </select>
                    @error('project_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="
                        dragging = false;
                        if ($event.dataTransfer.files.length) {
                            $refs.fileInput.files = $event.dataTransfer.files;
                            $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    "
                    :class="dragging ? 'border-zinc-500 bg-zinc-50 dark:bg-zinc-800' : 'border-zinc-300 dark:border-zinc-700'"
                    class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-10 text-center transition"
                >
                    <p class="mb-2 text-sm text-zinc-600 dark:text-zinc-300">Drag &amp; drop PDF files here, or click to browse</p>
                    <p class="mb-4 text-xs text-zinc-400">Up to {{ \App\Domains\Invoices\Livewire\Admin\Invoices\PdfImport::MAX_FILES }} files, 10 MB each</p>
                    <input type="file" x-ref="fileInput" wire:model="files" multiple accept="application/pdf" class="text-sm" />
                    <div wire:loading wire:target="files" class="mt-2 text-xs text-zinc-500">Uploading&hellip;</div>
                    @error('files') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                    @error('files.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                @if ($files)
                    <ul class="space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                        @foreach ($files as $file)
                            <li>{{ $file->getClientOriginalName() }}</li>
                        @endforeach
                    </ul>
                @endif

                <button type="submit" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                    Upload &amp; Process
                </button>
            </form>
        </div>
    @elseif (! $reviewing)
        {{-- Progress step --}}
        <div wire:poll.2s="pollStatus" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Processing PDFs&hellip;</h2>
            <ul class="space-y-2">
                @foreach ($importIds as $importId)
                    @php($import = $imports->get($importId))
                    @if ($import)
                        <li class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-2 text-sm dark:border-zinc-700">
                            <span class="text-zinc-700 dark:text-zinc-300">{{ basename($import->file_path) }}</span>
                            <span @class([
                                'inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' => $import->isPending(),
                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $import->isParsed(),
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' => $import->isFailed(),
                            ])>
                                {{ ucfirst($import->status) }}
                            </span>
                        </li>
                        @if ($import->isFailed() && $import->error_message)
                            <li class="px-4 text-xs text-red-600 dark:text-red-400">{{ $import->error_message }}</li>
                        @endif
                    @endif
                @endforeach
            </ul>
        </div>
    @else
        {{-- Review step --}}
        <form wire:submit="batchImport" class="space-y-6">
            @error('reviewRows') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            @foreach ($reviewRows as $index => $row)
                <div wire:key="review-row-{{ $row['import_id'] }}" class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            <input type="checkbox" wire:model="reviewRows.{{ $index }}.selected" class="rounded border-zinc-300" />
                            Include in import
                        </label>
                        <button type="button" wire:click="removeReviewRow({{ $index }})" class="text-xs font-semibold uppercase tracking-wide text-red-600 hover:underline">Remove</button>
                    </div>

                    <div
                        x-data="{ showPreview: false }"
                        class="mb-4 rounded-lg border border-zinc-200 dark:border-zinc-700"
                    >
                        <div class="flex items-center justify-between gap-3 px-4 py-2">
                            <span class="truncate text-xs font-medium text-zinc-600 dark:text-zinc-300" title="{{ $row['file_name'] ?? '' }}">
                                {{ $row['file_name'] ?? 'Source PDF' }}
                            </span>
                            <div class="flex shrink-0 items-center gap-3">
                                <button
                                    type="button"
                                    x-on:click="showPreview = ! showPreview"
                                    class="text-xs font-semibold uppercase tracking-wide text-zinc-700 hover:underline dark:text-zinc-200"
                                    x-text="showPreview ? 'Hide PDF' : 'View PDF'"
                                >View PDF</button>
                                <a
                                    href="{{ route('admin.invoices.import.preview', $row['import_id']) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-xs font-semibold uppercase tracking-wide text-zinc-500 hover:underline dark:text-zinc-400"
                                >Open in new tab</a>
                            </div>
                        </div>

                        {{-- Rendered only on demand so a large batch doesn't load every PDF at once. --}}
                        <template x-if="showPreview">
                            <div class="border-t border-zinc-200 p-3 dark:border-zinc-700">
                                <iframe
                                    src="{{ route('admin.invoices.import.preview', $row['import_id']) }}"
                                    class="h-[32rem] w-full rounded-md border border-zinc-200 bg-white dark:border-zinc-700"
                                    title="Preview of {{ $row['file_name'] ?? 'source PDF' }}"
                                ></iframe>
                            </div>
                        </template>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Vendor Name
                                @if (($row['confidence']['vendor_name'] ?? 0) < 0.6)
                                    <span title="Low confidence" class="text-amber-500">&#9888;</span>
                                @endif
                            </label>
                            <input type="text" wire:model="reviewRows.{{ $index }}.vendor_name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('reviewRows.'.$index.'.vendor_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Invoice Number
                                @if (($row['confidence']['invoice_number'] ?? 0) < 0.6)
                                    <span title="Low confidence" class="text-amber-500">&#9888;</span>
                                @endif
                            </label>
                            <input type="text" wire:model="reviewRows.{{ $index }}.invoice_number" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('reviewRows.'.$index.'.invoice_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Invoice Date
                                @if (($row['confidence']['invoice_date'] ?? 0) < 0.6)
                                    <span title="Low confidence" class="text-amber-500">&#9888;</span>
                                @endif
                            </label>
                            <input type="date" wire:model="reviewRows.{{ $index }}.invoice_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('reviewRows.'.$index.'.invoice_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Due Date
                                @if (($row['confidence']['due_date'] ?? 0) < 0.6)
                                    <span title="Low confidence" class="text-amber-500">&#9888;</span>
                                @endif
                            </label>
                            <input type="date" wire:model="reviewRows.{{ $index }}.due_date" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('reviewRows.'.$index.'.due_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Subtotal
                                @if (($row['confidence']['subtotal'] ?? 0) < 0.6)
                                    <span title="Low confidence" class="text-amber-500">&#9888;</span>
                                @endif
                            </label>
                            <input type="text" wire:model="reviewRows.{{ $index }}.subtotal" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('reviewRows.'.$index.'.subtotal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Tax Amount
                                @if (($row['confidence']['tax_amount'] ?? 0) < 0.6)
                                    <span title="Low confidence" class="text-amber-500">&#9888;</span>
                                @endif
                            </label>
                            <input type="text" wire:model="reviewRows.{{ $index }}.tax_amount" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('reviewRows.'.$index.'.tax_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 flex items-center gap-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                Total Amount
                                @if (($row['confidence']['total_amount'] ?? 0) < 0.6)
                                    <span title="Low confidence" class="text-amber-500">&#9888;</span>
                                @endif
                            </label>
                            <input type="text" wire:model="reviewRows.{{ $index }}.total_amount" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100" />
                            @error('reviewRows.'.$index.'.total_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if (! empty($row['line_items']))
                        <div class="mt-4">
                            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Line Items</h3>
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-1 text-left text-xs text-zinc-500">Description</th>
                                        <th class="px-2 py-1 text-left text-xs text-zinc-500">Qty</th>
                                        <th class="px-2 py-1 text-left text-xs text-zinc-500">Unit Price</th>
                                        <th class="px-2 py-1 text-left text-xs text-zinc-500">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($row['line_items'] as $lineIndex => $item)
                                        <tr wire:key="review-row-{{ $row['import_id'] }}-line-{{ $lineIndex }}">
                                            <td class="px-2 py-1">
                                                <input type="text" wire:model="reviewRows.{{ $index }}.line_items.{{ $lineIndex }}.description" class="w-full rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                                                @error('reviewRows.'.$index.'.line_items.'.$lineIndex.'.description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-2 py-1">
                                                <input type="text" wire:model="reviewRows.{{ $index }}.line_items.{{ $lineIndex }}.quantity" class="w-20 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                                                @error('reviewRows.'.$index.'.line_items.'.$lineIndex.'.quantity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-2 py-1">
                                                <input type="text" wire:model="reviewRows.{{ $index }}.line_items.{{ $lineIndex }}.unit_price" class="w-24 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                                                @error('reviewRows.'.$index.'.line_items.'.$lineIndex.'.unit_price') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                            <td class="px-2 py-1">
                                                <input type="text" wire:model="reviewRows.{{ $index }}.line_items.{{ $lineIndex }}.total" class="w-24 rounded border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-700 dark:bg-zinc-950" />
                                                @error('reviewRows.'.$index.'.line_items.'.$lineIndex.'.total') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endforeach

            @if (empty($reviewRows))
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                    No PDFs were successfully parsed for review.
                </div>
            @else
                <button type="submit" class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
                    Import Selected
                </button>
            @endif
        </form>
    @endif
</div>
