<div class="space-y-6">
    <!-- Create Share Button -->
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Document Shares</h3>
        <button
            wire:click="$set('showForm', true)"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium"
        >
            Create Share
        </button>
    </div>

    <!-- Create Share Form -->
    @if ($showForm)
        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 bg-zinc-50 dark:bg-zinc-900/50">
            <form wire:submit="createShare" class="space-y-4">
                <input
                    type="text"
                    name="username"
                    value="{{ auth()->user()?->email }}"
                    autocomplete="username"
                    tabindex="-1"
                    aria-hidden="true"
                    class="sr-only"
                />

                <!-- Password Field -->
                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">
                        Password (Optional)
                    </label>
                    <input
                        type="password"
                        wire:model="sharePassword"
                        placeholder="Leave empty for public access"
                        autocomplete="new-password"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                    />
                    @error('sharePassword')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expiration Field -->
                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">
                        Expires At (Optional)
                    </label>
                    <input
                        type="datetime-local"
                        wire:model="expiresAt"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                    />
                    @error('expiresAt')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Download Limit Field -->
                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">
                        Max Downloads (Optional)
                    </label>
                    <input
                        type="number"
                        wire:model="maxDownloads"
                        min="1"
                        placeholder="Unlimited if empty"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                    />
                    @error('maxDownloads')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Access Notes Field -->
                <div>
                    <label class="block text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">
                        Access Notes (Optional)
                    </label>
                    <textarea
                        wire:model="accessNotes"
                        placeholder="Add any notes about this share"
                        rows="2"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                    ></textarea>
                    @error('accessNotes')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Form Actions -->
                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium"
                    >
                        Create Share
                    </button>
                    <button
                        type="button"
                        wire:click="$set('showForm', false)"
                        class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 transition text-sm font-medium"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Shares List -->
    @if (count($shares) > 0)
        <div class="space-y-3">
            @foreach ($shares as $share)
                <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 bg-white dark:bg-zinc-900">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <!-- Share Token -->
                            <div class="mb-2">
                                <p class="text-sm font-mono text-zinc-600 dark:text-zinc-400 break-all">
                                    {{ route('share.view', $share['share_token']) }}
                                </p>
                            </div>

                            <!-- Share Details -->
                            <div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                                @if ($share['share_password'])
                                    <p>🔒 Password protected</p>
                                @endif
                                @if ($share['expires_at'])
                                    <p>📅 Expires: {{ \Carbon\Carbon::parse($share['expires_at'])->format('M d, Y H:i') }}</p>
                                @endif
                                @if ($share['max_downloads'])
                                    <p>📥 Downloads: {{ $share['download_count'] }} / {{ $share['max_downloads'] }}</p>
                                @else
                                    <p>📥 Downloads: {{ $share['download_count'] }}</p>
                                @endif
                                @if ($share['access_notes'])
                                    <p>💬 Notes: {{ $share['access_notes'] }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 ml-4">
                            <button
                                wire:click="toggleShare('{{ $share['id'] }}')"
                                class="px-3 py-1 text-sm {{ $share['is_active'] ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-zinc-200 text-zinc-700 hover:bg-zinc-300' }} rounded transition"
                            >
                                {{ $share['is_active'] ? '✓ Active' : '✗ Disabled' }}
                            </button>
                            <button
                                wire:click="deleteShare('{{ $share['id'] }}')"
                                wire:confirm="Are you sure you want to delete this share?"
                                class="px-3 py-1 text-sm bg-red-100 text-red-700 hover:bg-red-200 rounded transition"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
            <p>No shares created yet. Create one to share this document!</p>
        </div>
    @endif
</div>
