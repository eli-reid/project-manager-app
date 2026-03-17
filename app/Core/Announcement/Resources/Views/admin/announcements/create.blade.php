<x-layouts::admin :title="__('Create Announcement')">
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Create Announcement') }}</h1>
        </div>

        @include('announcement::admin.announcements.partials.form', [
            'announcement' => null,
            'types' => $types,
            'action' => route('admin.announcements.store'),
            'method' => 'POST',
        ])
    </div>
</x-layouts::admin>
