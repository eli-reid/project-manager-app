<div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Create Mailbox</flux:heading>
            <flux:text class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Create a new cPanel mailbox for an existing user or as a standalone account.</flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.email-accounts.index')" icon="inbox-stack" wire:navigate>Back to Accounts</flux:button>
        </div>
    </div>

    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>Link To Existing User (Optional)</flux:label>
                <flux:select wire:model.live="selectedUserId">
                    <option value="">Standalone mailbox</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">
                            {{ trim($user->first_name.' '.$user->last_name) }} ({{ $user->username }})
                        </option>
                    @endforeach
                </flux:select>
                <flux:text size="sm">When selected, mailbox username is taken from the user username.</flux:text>
                <flux:error name="selectedUserId" />
            </flux:field>

            <flux:field>
                <flux:label>Username</flux:label>
                <flux:input wire:model="username" placeholder="john.doe" />
                <flux:text size="sm">Letters, numbers, dot, underscore, dash only.</flux:text>
                <flux:error name="username" />
            </flux:field>

            <flux:field>
                <div class="flex items-center justify-between gap-2">
                    <flux:label>Password</flux:label>
                    <flux:button size="xs" variant="ghost" type="button" wire:click="generatePassword">Generate</flux:button>
                </div>
                <flux:input type="text" wire:model="password" />
                <flux:text size="sm">Minimum 12 characters.</flux:text>
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Quota (MB)</flux:label>
                <flux:input type="number" min="0" wire:model="quota" />
                <flux:text size="sm">Set to 0 for unlimited if your cPanel setup supports it.</flux:text>
                <flux:error name="quota" />
            </flux:field>
        </div>

        <div class="mt-5 flex items-center gap-2">
            <flux:button variant="primary" wire:click="createMailbox" icon="plus">Create Mailbox</flux:button>
            <flux:button variant="ghost" :href="route('admin.cpanel.manage.email-accounts.index')" wire:navigate>Cancel</flux:button>
        </div>
    </div>
</div>
