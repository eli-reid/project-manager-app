<?php

namespace App\Core\Notification\Livewire\Admin;

use App\Core\Notification\Services\NotificationRegistry;
use App\Core\Notification\Settings\NotificationSettings;
use App\Core\Settings\Facades\Settings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Livewire\Component;

class ChannelMatrix extends Component
{
    use AuthorizesRequests;

    public bool $notificationsEnabled = true;

    /**
     * @var array<string, bool>
     */
    public array $defaultChannels = [];

    /**
     * @var array<string, array<string, bool>>
     */
    public array $channels = [];

    /**
     * @var array<int, array{key:string,label:string,description:string,domain:string,channels:array<int, array{key:string,label:string,enabled:bool,supported:bool}>}>
     */
    public array $definitions = [];

    public ?string $successMessage = null;

    public static function notificationFormKey(string $notificationKey): string
    {
        return rtrim(strtr(base64_encode($notificationKey), '+/', '-_'), '=');
    }

    public function mount(NotificationRegistry $notificationRegistry): void
    {
        $this->authorize('settings.view');

        $this->loadDefinitions($notificationRegistry);
    }

    public function save(): void
    {
        $this->authorize('settings.edit');

        $this->persistGlobalSettings();

        foreach ($this->definitions as $definition) {
            $notificationKey = (string) $definition['key'];
            $formKey = (string) ($definition['form_key'] ?? self::notificationFormKey($notificationKey));
            $settingKey = NotificationSettings::allowedChannelsSettingKey($notificationKey);
            $supportedChannels = collect($definition['channels'])
                ->filter(fn (array $channel): bool => (bool) $channel['supported'])
                ->pluck('key')
                ->values()
                ->all();

            $enabledChannels = collect($supportedChannels)
                ->filter(fn (string $channel): bool => (bool) ($this->channels[$formKey][$channel] ?? false))
                ->values()
                ->all();

            Settings::set($settingKey, json_encode($enabledChannels, JSON_THROW_ON_ERROR));
        }

        $this->successMessage = 'Notification channel rules updated.';
        $this->dispatch('notification-channel-matrix-saved');
    }

    public function render()
    {
        return view('core-notification::livewire.admin.channel-matrix', [
            'groupedDefinitions' => collect($this->definitions)
                ->groupBy('domain')
                ->map(function ($definitions, string $domain): array {
                    return [
                        'key' => $domain,
                        'label' => str($domain)->headline()->value(),
                        'definitions' => $definitions->values()->all(),
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    private function loadDefinitions(NotificationRegistry $notificationRegistry): void
    {
        $availableChannels = $this->availableChannels();

        $this->notificationsEnabled = Settings::get('notifications.enabled', true)->toBool();

        $this->defaultChannels = collect($availableChannels)
            ->mapWithKeys(function (string $channel): array {
                return [$channel => in_array($channel, $this->defaultEnabledChannels(), true)];
            })
            ->all();

        $this->definitions = collect($notificationRegistry->definitions())
            ->map(function (array $definition) use ($availableChannels): array {
                $notificationKey = (string) $definition['key'];
                $supportedChannels = collect($definition['supported_channels'] ?? [])
                    ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
                    ->values()
                    ->all();
                $enabledChannels = collect(
                    Settings::get(
                        NotificationSettings::allowedChannelsSettingKey($notificationKey),
                        $supportedChannels,
                    )->toArray($supportedChannels)
                )
                    ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
                    ->values();

                return [
                    'key' => $notificationKey,
                    'form_key' => self::notificationFormKey($notificationKey),
                    'label' => (string) $definition['label'],
                    'description' => (string) $definition['description'],
                    'domain' => Arr::first(explode('.', $notificationKey)) ?? 'general',
                    'channels' => collect($availableChannels)
                        ->map(function (string $channel) use ($enabledChannels, $supportedChannels): array {
                            return [
                                'key' => $channel,
                                'label' => $this->channelLabel($channel),
                                'enabled' => $enabledChannels->contains($channel),
                                'supported' => in_array($channel, $supportedChannels, true),
                            ];
                        })
                        ->all(),
                ];
            })
            ->sortBy(['domain', 'label'])
            ->values()
            ->all();

        $this->channels = collect($this->definitions)
            ->mapWithKeys(function (array $definition): array {
                return [
                    $definition['form_key'] => collect($definition['channels'])
                        ->mapWithKeys(fn (array $channel): array => [$channel['key'] => (bool) $channel['enabled']])
                        ->all(),
                ];
            })
            ->all();
    }

    private function channelLabel(string $channel): string
    {
        return match ($channel) {
            'database' => 'In-app',
            'mail' => 'Email',
            'sms' => 'SMS',
            'push' => 'Push',
            default => str($channel)->headline()->value(),
        };
    }

    /**
     * @return array<int, string>
     */
    private function availableChannels(): array
    {
        return ['database', 'mail', 'sms', 'push'];
    }

    /**
     * @return array<int, string>
     */
    private function defaultEnabledChannels(): array
    {
        return collect(Settings::get('notifications.default_channels', ['mail', 'database'])->toArray(['mail', 'database']))
            ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
            ->values()
            ->all();
    }

    private function persistGlobalSettings(): void
    {
        $settings = [
            'notifications.enabled' => $this->notificationsEnabled ? 'true' : 'false',
            'notifications.default_channels' => json_encode(
                collect($this->availableChannels())
                    ->filter(fn (string $channel): bool => (bool) ($this->defaultChannels[$channel] ?? false))
                    ->values()
                    ->all(),
                JSON_THROW_ON_ERROR
            ),
        ];

        foreach ($settings as $key => $value) {
            Settings::set($key, $value);
        }
    }
}
