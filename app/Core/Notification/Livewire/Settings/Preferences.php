<?php

namespace App\Core\Notification\Livewire\Settings;

use App\Core\Notification\Services\NotificationPreferenceService;
use App\Domains\Timecards\Notifications\TimecardNotificationDefinitions;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Notification settings')]
class Preferences extends Component
{
    /**
     * @var array<string, array<string, bool>>
     */
    public array $preferences = [];

    /**
     * @var array<int, array{key:string,label:string,description:string,channels:array<int, array{key:string,label:string,enabled:bool,supported:bool}>}>
     */
    public array $definitions = [];

    public static function notificationFormKey(string $notificationKey): string
    {
        return rtrim(strtr(base64_encode($notificationKey), '+/', '-_'), '=');
    }

    public function mount(NotificationPreferenceService $notificationPreferenceService): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 403);

        $matrix = $notificationPreferenceService->preferenceMatrixFor($user);
        $this->definitions = collect($matrix)
            ->map(function (array $definition): array {
                $definition['form_key'] = self::notificationFormKey($definition['key']);

                return $definition;
            })
            ->all();
        $this->preferences = collect($matrix)
            ->mapWithKeys(function (array $definition): array {
                return [
                    self::notificationFormKey($definition['key']) => collect($definition['channels'])
                        ->mapWithKeys(fn (array $channel): array => [$channel['key'] => (bool) $channel['enabled']])
                        ->all(),
                ];
            })
            ->all();
    }

    public function save(NotificationPreferenceService $notificationPreferenceService): void
    {
        $user = Auth::user();

        abort_unless($user !== null, 403);

        if (! $this->validateRequiredReminderChannels()) {
            return;
        }

        $preferences = collect($this->definitions)
            ->mapWithKeys(function (array $definition): array {
                $formKey = $definition['form_key'] ?? self::notificationFormKey($definition['key']);

                return [$definition['key'] => $this->preferences[$formKey] ?? []];
            })
            ->all();

        $notificationPreferenceService->syncPreferences($user, $preferences);
        $this->dispatch('preferences-saved');
    }

    private function validateRequiredReminderChannels(): bool
    {
        $this->resetErrorBag('preferences');

        $requiredKeys = [
            TimecardNotificationDefinitions::REMINDER,
            TimecardNotificationDefinitions::MISSING_REMINDER,
        ];

        foreach ($this->definitions as $definition) {
            $notificationKey = (string) ($definition['key'] ?? '');

            if (! in_array($notificationKey, $requiredKeys, true)) {
                continue;
            }

            $formKey = (string) ($definition['form_key'] ?? self::notificationFormKey($notificationKey));
            $selected = collect($definition['channels'] ?? [])
                ->filter(fn (array $channel): bool => (bool) ($channel['supported'] ?? false))
                ->contains(fn (array $channel): bool => (bool) ($this->preferences[$formKey][$channel['key']] ?? false));

            if (! $selected) {
                $this->addError(
                    'preferences.'.$formKey,
                    __('Select at least one delivery method for :notification.', [
                        'notification' => (string) ($definition['label'] ?? __('Timecard Reminder')),
                    ])
                );
            }
        }

        return $this->getErrorBag()->isEmpty();
    }

    public function render()
    {
        $view = request()->routeIs('settings.mobile.*')
            ? view('core-notification::livewire.mobile.settings.preferences')
            : view('core-notification::livewire.settings.preferences');

        if (request()->routeIs('settings.mobile.*')) {
            return $view->layout('layouts.mobile', ['title' => __('Notification settings')]);
        }

        return $view;
    }
}
