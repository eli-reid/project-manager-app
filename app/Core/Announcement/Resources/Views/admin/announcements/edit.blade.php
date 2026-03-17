<x-layouts::admin :title="__('Edit Announcement')">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Edit Announcement') }}</h1>
        </div>

        @include('announcement::admin.announcements.partials.form', [
            'announcement' => $announcement,
            'types' => $types,
            'action' => route('admin.announcements.update', $announcement),
            'method' => 'PUT',
        ])
    </div>
</x-layouts::admin>
