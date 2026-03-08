<?php

namespace App\Core\Settings\Livewire;

use App\Core\Settings\Models\SettingsSqlite;
use App\Core\Settings\Services\SettingsCacheService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * SettingsEditor Component
 *
 * Edit settings within a selected group. Supports all field types:
 * text, textarea, number, email, url, select, boolean, password, etc.
 */
class SettingsEditor extends Component
{
    use AuthorizesRequests;

    /**
     * Current group being edited
     */
    public string $group = '';

    /**
     * Form data (key => value)
     */
    public array $formData = [];

    /**
     * Settings metadata (key => metadata)
     */
    public array $settingsMetadata = [];

    /**
     * Internal field id => real setting key map
     */
    public array $keyMap = [];

    /**
     * Validation errors
     */
    public array $validationErrors = [];

    /**
     * Success message
     */
    public ?string $successMessage = null;

    /**
     * Error message
     */
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', SettingsSqlite::class);
        $this->listenForGroupSelection();
    }

    /**
     * Listen for group selection event from SettingsGroupList
     */
    public function listenForGroupSelection(): void
    {
        $this->dispatch('listen', 'group-selected', 'loadSettings');
    }

    /**
     * Load settings for selected group
     */
    #[\Livewire\Attributes\On('group-selected')]
    public function loadSettings(string $group): void
    {
        $this->authorize('viewAny', SettingsSqlite::class);

        $this->group = $group;
        $this->formData = [];
        $this->settingsMetadata = [];
        $this->keyMap = [];
        $this->successMessage = null;
        $this->errorMessage = null;
        $this->validationErrors = [];

        $settings = SettingsSqlite::where('group', $group)
            ->where('is_visible', true)
            ->orderBy('order')
            ->get();

        foreach ($settings as $setting) {
            $fieldId = $this->fieldIdForKey($setting->key);

            $this->keyMap[$fieldId] = $setting->key;
            $this->formData[$fieldId] = $setting->value;
            $this->settingsMetadata[$fieldId] = [
                'setting_key' => $setting->key,
                'type' => $setting->type,
                'display_name' => $setting->display_name,
                'description' => $setting->description,
                'is_required' => $setting->is_required,
                'options' => is_array($setting->options)
                    ? $setting->options
                    : ($setting->options ? json_decode($setting->options, true) : null),
                'encrypted' => $setting->encrypted,
                'order' => $setting->order,
            ];
        }
    }

    /**
     * Update a single setting
     */
    public function updateSetting(string $key): void
    {
        $this->authorize('update', SettingsSqlite::class);

        try {
            $settingKey = $this->resolveSettingKey($key);
            $setting = SettingsSqlite::where('key', $settingKey)->firstOrFail();
            $value = $this->formData[$key] ?? $this->formData[$settingKey] ?? '';

            // Validate input
            $this->validateSetting($key, $value, $setting);

            // Update in database
            $setting->update(['value' => $value]);

            // Clear caches
            $this->cacheService()->forget($settingKey);
            $this->cacheService()->flushNamespace($setting->group);

            // Show success
            $this->successMessage = "Setting '{$setting->display_name}' updated successfully!";
            $this->errorMessage = null;

            // Clear message after 5 seconds
            $this->dispatch('clear-message-timeout');

        } catch (ValidationException $e) {
            $this->validationErrors[$key] = $e->validator->errors()->first();
            $this->errorMessage = 'Validation error. Please check your input.';
        } catch (\Exception $e) {
            Log::error("Failed to update setting {$key}: ".$e->getMessage());
            $this->errorMessage = 'Failed to update setting: '.$e->getMessage();
        }
    }

    /**
     * Update all settings in the group
     */
    public function updateAllSettings(): void
    {
        $this->authorize('update', SettingsSqlite::class);

        try {
            $updatedCount = 0;
            $errors = [];

            foreach ($this->formData as $key => $value) {
                try {
                    $settingKey = $this->resolveSettingKey($key);
                    $setting = SettingsSqlite::where('key', $settingKey)->first();
                    if (! $setting) {
                        continue;
                    }

                    // Validate
                    $this->validateSetting($settingKey, $value, $setting);

                    // Update
                    $setting->update(['value' => $value]);
                    $this->cacheService()->forget($settingKey);
                    $updatedCount++;

                } catch (ValidationException $e) {
                    $errors[] = "{$setting->display_name}: ".$e->validator->errors()->first();
                }
            }

            // Clear group cache
            if ($this->group) {
                $this->cacheService()->flushNamespace($this->group);
            }

            if ($errors) {
                $this->errorMessage = implode("\n", $errors);
                $this->successMessage = null;
            } else {
                $this->successMessage = "All settings in '{$this->group}' updated successfully! ({$updatedCount} changes)";
                $this->errorMessage = null;
            }

        } catch (\Exception $e) {
            Log::error("Failed to update settings for group {$this->group}: ".$e->getMessage());
            $this->errorMessage = 'Failed to update settings: '.$e->getMessage();
        }
    }

    /**
     * Reset form to last saved values
     */
    public function resetForm(): void
    {
        $this->authorize('viewAny', SettingsSqlite::class);

        $this->loadSettings($this->group);
        $this->successMessage = 'Form reset to last saved values.';
        $this->errorMessage = null;
    }

    /**
     * Validate a setting value
     */
    private function validateSetting(string $key, mixed $value, SettingsSqlite $setting): void
    {
        $rules = [];

        if ($setting->is_required) {
            $rules['value'] = 'required';
        }

        switch ($setting->type) {
            case 'email':
                $rules['value'] = ($rules['value'] ?? '').'|email';
                break;
            case 'url':
                $rules['value'] = ($rules['value'] ?? '').'|url';
                break;
            case 'number':
            case 'integer':
                $rules['value'] = ($rules['value'] ?? '').'|numeric';
                break;
            case 'boolean':
                if ($value !== 'true' && $value !== 'false' && $value !== '0' && $value !== '1') {
                    throw ValidationException::withMessages([
                        'value' => 'Must be true or false',
                    ]);
                }
                break;
        }

        if (! empty($rules)) {
            Validator::make(
                ['value' => $value],
                ['value' => implode('|', array_filter(explode('|', $rules['value'] ?? '')))],
                [],
                ['value' => $setting->display_name]
            )->validate();
        }
    }

    private function cacheService(): SettingsCacheService
    {
        return app(SettingsCacheService::class);
    }

    private function fieldIdForKey(string $key): string
    {
        return 'setting_'.md5($key);
    }

    private function resolveSettingKey(string $key): string
    {
        return $this->keyMap[$key] ?? $key;
    }

    /**
     * Get icon for field type
     */
    public function getFieldIcon(string $type): string
    {
        return match ($type) {
            'text' => 'edit',
            'textarea' => 'align-left',
            'email' => 'mail',
            'url' => 'link',
            'number', 'integer' => 'hash',
            'select' => 'list',
            'boolean' => 'toggle-2',
            'password' => 'lock',
            'date' => 'calendar',
            'array' => 'layers',
            default => 'settings',
        };
    }

    public function render()
    {
        return view('core::livewire.settings-editor', [
            'formData' => $this->formData,
            'settingsMetadata' => $this->settingsMetadata,
            'group' => $this->group,
            'successMessage' => $this->successMessage,
            'errorMessage' => $this->errorMessage,
            'validationErrors' => $this->validationErrors,
        ]);
    }
}
