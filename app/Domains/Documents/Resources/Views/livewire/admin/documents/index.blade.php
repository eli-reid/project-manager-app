<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Documents Admin</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Review all documents, reclaim space, and delete any document as an administrator.</flux:text>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('success') }}</div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Documents</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['documents_count'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Storage Used</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $this->formatBytes($summary['total_bytes']) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">User Owned</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['user_owned_count'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project Owned</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['project_owned_count'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Global</p>
            <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['global_count'] }}</p>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[2fr_1fr]">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid gap-3 md:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Search</label>
                    <input type="text" wire:model.live="search" placeholder="Title, file, path..." class="w-full rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Owner Scope</label>
                    <select wire:model.live="filterOwnerScope" class="w-full rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                        <option value="">All Scopes</option>
                        @foreach ($ownerScopes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Disk</label>
                    <select wire:model.live="filterDisk" class="w-full rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                        <option value="">All Disks</option>
                        @foreach ($disks as $disk)
                            <option value="{{ $disk['disk'] }}">{{ $disk['disk'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Disk Space</h2>
            <div class="mt-3 space-y-3">
                @forelse ($disks as $disk)
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $disk['disk'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $disk['documents_count'] }} docs</p>
                        </div>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Used: {{ $this->formatBytes($disk['total_bytes']) }}</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            @if ($disk['free_bytes'] !== null && $disk['total_space_bytes'] !== null)
                                Free: {{ $this->formatBytes($disk['free_bytes']) }} / {{ $this->formatBytes($disk['total_space_bytes']) }}
                            @else
                                Free space unavailable for this disk.
                            @endif
                        </p>
                        @if ($disk['root'])
                            <p class="mt-1 break-all text-[11px] text-zinc-400 dark:text-zinc-500">{{ $disk['root'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">No documents stored yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Document</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Owner</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Disk</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Size</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Visibility</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Uploaded</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($documents as $document)
                        <tr wire:key="admin-document-{{ $document->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                            <td class="px-4 py-3 align-top">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $document->title }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $document->original_name }}</div>
                                <div class="mt-1 text-[11px] text-zinc-400 dark:text-zinc-500">{{ $document->storage_path }}</div>
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-zinc-600 dark:text-zinc-300">
                                @if ($document->owner_scope === \App\Domains\Documents\Models\Document::OWNER_SCOPE_PROJECT)
                                    {{ $document->ownerProject?->name ?? '—' }}
                                @else
                                    {{ trim(($document->ownerUser?->first_name ?? '').' '.($document->ownerUser?->last_name ?? '')) ?: '—' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-zinc-600 dark:text-zinc-300">{{ $document->storage_disk }}</td>
                            <td class="px-4 py-3 align-top text-xs text-zinc-600 dark:text-zinc-300">{{ $this->formatBytes((int) $document->file_size) }}</td>
                            <td class="px-4 py-3 align-top text-xs text-zinc-600 dark:text-zinc-300">{{ str($document->visibility)->headline() }}</td>
                            <td class="px-4 py-3 align-top text-xs text-zinc-500 dark:text-zinc-400">{{ $document->created_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3 align-top text-right">
                                <button type="button" wire:click="deleteDocument('{{ $document->id }}')" wire:confirm="Delete this document and free its storage?" class="rounded-md border border-red-300 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-300 dark:hover:bg-red-900/20">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No documents found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
            {{ $documents->links() }}
        </div>
    </div>
</div>
