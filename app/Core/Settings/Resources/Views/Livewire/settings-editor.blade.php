<div wire:init="loadSettings('{{ $group }}')">
    @if (!$group)
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <div>
                <strong>Select a Group</strong>
                <p class="mb-0">Choose a setting group from the left panel to begin editing.</p>
            </div>
        </div>
    @else
        <!-- Success Message -->
        @if ($successMessage)
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> {{ $successMessage }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Error Message -->
        @if ($errorMessage)
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Error!</strong> {{ $errorMessage }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Group Title -->
        <div class="mb-4">
            <h5>
                <i class="fas fa-folder-open me-2"></i>
                {{ ucfirst(str_replace('_', ' ', $group)) }} Settings
            </h5>
            <p class="text-muted mb-0">
                Manage {{ count($settingsMetadata) }} settings in this group
            </p>
        </div>

        <!-- Settings Form -->
        <form wire:submit="updateAllSettings">
            <div class="row">
                @forelse($settingsMetadata as $key => $meta)
                    <div class="col-12 mb-4">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <!-- Field Label & Description -->
                                <label for="{{ $key }}" class="form-label mb-1">
                                    <i class="fas fa-{{ $this->getFieldIcon($meta['type']) }} me-1 text-primary"></i>
                                    {{ $meta['display_name'] }}
                                    @if ($meta['is_required'])
                                        <span class="badge bg-danger">Required</span>
                                    @endif
                                    @if ($meta['encrypted'])
                                        <span class="badge bg-warning">Encrypted</span>
                                    @endif
                                </label>
                                <small class="text-muted d-block mb-2">{{ $meta['description'] }}</small>

                                <!-- Form Field Based on Type -->
                                @if ($meta['type'] === 'textarea')
                                    <textarea
                                        id="{{ $key }}"
                                        class="form-control @error($key) is-invalid @enderror"
                                        wire:model="formData.{{ $key }}"
                                        wire:change="updateSetting('{{ $key }}')"
                                        rows="4"
                                        placeholder="Enter value..."
                                    ></textarea>

                                @elseif ($meta['type'] === 'select')
                                    <select
                                        id="{{ $key }}"
                                        class="form-select @error($key) is-invalid @enderror"
                                        wire:model="formData.{{ $key }}"
                                        wire:change="updateSetting('{{ $key }}')"
                                    >
                                        <option value="">-- Select an option --</option>
                                        @if ($meta['options'])
                                            @foreach ($meta['options'] as $optionKey => $optionLabel)
                                                <option value="{{ $optionKey }}">{{ $optionLabel }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                @elseif ($meta['type'] === 'boolean')
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="{{ $key }}"
                                            wire:model="formData.{{ $key }}"
                                            value="true"
                                            {{ ($formData[$key] ?? '') === 'true' || ($formData[$key] ?? '') === '1' ? 'checked' : '' }}
                                            wire:change="updateSetting('{{ $key }}')"
                                        >
                                        <label class="form-check-label" for="{{ $key }}">
                                            Enabled
                                        </label>
                                    </div>

                                @elseif ($meta['type'] === 'password')
                                    <div class="input-group">
                                        <input
                                            type="password"
                                            id="{{ $key }}"
                                            class="form-control @error($key) is-invalid @enderror"
                                            wire:model="formData.{{ $key }}"
                                            placeholder="Leave blank to keep current value"
                                            autocomplete="off"
                                        >
                                        <button
                                            class="btn btn-outline-secondary"
                                            type="button"
                                            onclick="togglePasswordField('{{ $key }}')"
                                        >
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>

                                @elseif ($meta['type'] === 'email')
                                    <input
                                        type="email"
                                        id="{{ $key }}"
                                        class="form-control @error($key) is-invalid @enderror"
                                        wire:model="formData.{{ $key }}"
                                        wire:change="updateSetting('{{ $key }}')"
                                        placeholder="user@example.com"
                                    >

                                @elseif ($meta['type'] === 'url')
                                    <input
                                        type="url"
                                        id="{{ $key }}"
                                        class="form-control @error($key) is-invalid @enderror"
                                        wire:model="formData.{{ $key }}"
                                        wire:change="updateSetting('{{ $key }}')"
                                        placeholder="https://example.com"
                                    >

                                @elseif ($meta['type'] === 'number' || $meta['type'] === 'integer')
                                    <input
                                        type="number"
                                        id="{{ $key }}"
                                        class="form-control @error($key) is-invalid @enderror"
                                        wire:model="formData.{{ $key }}"
                                        wire:change="updateSetting('{{ $key }}')"
                                        placeholder="0"
                                    >

                                @else
                                    <!-- Default: text input -->
                                    <input
                                        type="text"
                                        id="{{ $key }}"
                                        class="form-control @error($key) is-invalid @enderror"
                                        wire:model="formData.{{ $key }}"
                                        wire:change="updateSetting('{{ $key }}')"
                                        placeholder="Enter value..."
                                    >
                                @endif

                                <!-- Validation Error -->
                                @if (isset($validationErrors[$key]))
                                    <div class="invalid-feedback d-block mt-1">
                                        <i class="fas fa-times-circle me-1"></i>
                                        {{ $validationErrors[$key] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No settings found in this group.
                    </div>
                @endforelse
            </div>

            <!-- Form Actions -->
            @if (!empty($settingsMetadata))
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <i class="fas fa-save me-1"></i>
                        <span wire:loading.remove>Save All Changes</span>
                        <span wire:loading>
                            <i class="spinner-border spinner-border-sm me-1"></i>
                            Saving...
                        </span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" wire:click="resetForm">
                        <i class="fas fa-redo me-1"></i>
                        Reset
                    </button>
                </div>
            @endif
        </form>
    @endif
</div>

<script>
function togglePasswordField(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}
</script>
