<div class="mx-auto w-full max-w-3xl space-y-4 px-4 py-6 sm:px-6 lg:px-8">
    <flux:heading size="xl">{{ $submittal ? 'Edit Submittal' : 'Create Submittal' }}</flux:heading>

    <form wire:submit="save" class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:field>
            <flux:label>Project</flux:label>
            <flux:select wire:model="projectId">
                <option value="">Select project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->project_number ?? 'N/A' }})</option>
                @endforeach
            </flux:select>
            <flux:error name="projectId" />
        </flux:field>

        <flux:field>
            <flux:label>Package Type</flux:label>
            <flux:input wire:model="type" placeholder="Lighting fixture package, gear package, etc." />
            <flux:error name="type" />
        </flux:field>

        <div class="grid gap-4 md:grid-cols-2">
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
        </div>

        <flux:field>
            <flux:label>Need-By Date</flux:label>
            <flux:input type="date" wire:model="needByDate" />
            <flux:error name="needByDate" />
        </flux:field>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ $submittal ? route('submittals.show', $submittal) : route('submittals.index') }}" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancel</a>
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
