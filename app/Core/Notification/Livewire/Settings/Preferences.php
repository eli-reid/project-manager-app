<?php

namespace App\Core\Notification\Livewire\Settings;

use App\Core\Notification\Services\NotificationPreferenceService;
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

        $preferences = collect($this->definitions)
            ->mapWithKeys(function (array $definition): array {
                $formKey = $definition['form_key'] ?? self::notificationFormKey($definition['key']);

                return [$definition['key'] => $this->preferences[$formKey] ?? []];
            })
            ->all();

        $notificationPreferenceService->syncPreferences($user, $preferences);
        $this->dispatch('preferences-saved');
    }

    public function render()
    {
        return view('core-notification::livewire.settings.preferences');
    }
}
