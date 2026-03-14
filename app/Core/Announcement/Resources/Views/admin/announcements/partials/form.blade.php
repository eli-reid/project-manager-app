@php
    $announcementType = old('type', $announcement?->type?->value ?? 'general');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="title" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Title') }}</label>
        <input id="title" name="title" value="{{ old('title', $announcement?->title) }}" required class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
        @error('title')
            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="content" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Content') }}</label>
        <textarea id="content" name="content" rows="6" required class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">{{ old('content', $announcement?->content) }}</textarea>
        @error('content')
            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="type" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Type') }}</label>
            <select id="type" name="type" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                @foreach ($types as $type)
                    <option value="{{ $type['value'] }}" @selected($announcementType === $type['value'])>{{ $type['label'] }}</option>
                @endforeach
            </select>
            @error('type')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid gap-3 pt-6">
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $announcement?->is_active ?? true))>
                <span>{{ __('Active') }}</span>
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                <input type="checkbox" name="is_dismissable" value="1" @checked(old('is_dismissable', $announcement?->is_dismissable ?? false))>
                <span>{{ __('Dismissable') }}</span>
            </label>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="start_date" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Start Date') }}</label>
            <input id="start_date" name="start_date" type="datetime-local" value="{{ old('start_date', $announcement?->start_date?->format('Y-m-d\\TH:i')) }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
            @error('start_date')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="end_date" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('End Date') }}</label>
            <input id="end_date" name="end_date" type="datetime-local" value="{{ old('end_date', $announcement?->end_date?->format('Y-m-d\\TH:i')) }}" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
            @error('end_date')
                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <button type="submit" class="rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-300">
            {{ __('Save Announcement') }}
        </button>
    </div>
</form>
