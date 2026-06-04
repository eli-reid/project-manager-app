<div class="w-full space-y-4">
    @if ($embeddedProject)
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $submittalCount }} {{ Str::plural('submittal', $submittalCount) }} for this project.
            </p>
            @can('create', \App\Domains\Submittals\Models\Submittal::class)
                <a href="{{ $submittalCreateUrl }}" wire:navigate class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">+ New Submittal</a>
            @endcan
        </div>
    @else
        <div class="flex items-center justify-between gap-3">
            <flux:heading size="xl">Submittal Approval Queue</flux:heading>
            <a href="{{ route('submittals.index') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">User View</a>
        </div>
    @endif

    @if ($isCreateMode && $embeddedProject)
        <livewire:submittals::submittals.form :projectId="$embeddedProject->id" :returnTo="$projectSubmittalsUrl" :embedded="true" :key="'project-submittal-create-'.$embeddedProject->id" />
    @elseif ($isReviewMode && $reviewSubmittal instanceof \App\Domains\Submittals\Models\Submittal)
        <livewire:submittals::admin.submittals.show
            :submittal="$reviewSubmittal"
            :embedded="true"
            :returnTo="$projectSubmittalsUrl"
            :key="'project-submittal-review-'.$embeddedProject->id.'-'.$reviewSubmittal->id"
        />
    @else

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</label>
                <select wire:model.live="status" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Document Role</label>
                <select wire:model.live="documentRole" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Any role</option>
                    @foreach ($documentRoles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Document Status</label>
                <select wire:model.live="documentStatus" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Any document status</option>
                    @foreach ($documentStatuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Discipline</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="documentDiscipline"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="Electrical"
                />
            </div>

            <div>
                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Revision</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="documentRevision"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    placeholder="Rev B"
                />
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Type</th>
                        @unless ($embeddedProject)
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Project</th>
                        @endunless
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Submitted By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($submittals as $submittal)
                        <tr>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">{{ $submittal->type }}</td>
                            @unless ($embeddedProject)
                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $submittal->project?->name ?? '—' }}</td>
                            @endunless
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ trim(($submittal->submittedBy?->first_name ?? '').' '.($submittal->submittedBy?->last_name ?? '')) ?: '—' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $submittal->statusLabel() }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ $embeddedProject ? route('admin.projects.show', ['project' => $embeddedProject, 'tab' => 'submittals', 'submittalMode' => 'review', 'submittalId' => $submittal->id]) : route('admin.submittals.show', $submittal) }}" wire:navigate class="font-medium text-zinc-700 hover:underline dark:text-zinc-200">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $embeddedProject ? 4 : 5 }}" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No submittals in the queue.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

        @if ($submittals->hasPages())
            <div>{{ $submittals->links() }}</div>
        @endif
    @endif
</div>
