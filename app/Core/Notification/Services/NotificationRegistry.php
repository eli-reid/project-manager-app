<?php

namespace App\Core\Notification\Services;

class NotificationRegistry
{
    /**
     * @var array<string, array{key:string,label:string,description:string,supported_channels:array<int, string>}>
     */
    private array $definitions = [];

    /**
     * @param  array<int, array{key:string,label?:string,description?:string,supported_channels?:array<int, string>}>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            $key = (string) ($definition['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $channels = collect($definition['supported_channels'] ?? [])
                ->filter(fn (mixed $channel): bool => is_string($channel) && $channel !== '')
                ->unique()
                ->values()
                ->all();

            $this->definitions[$key] = [
                'key' => $key,
                'label' => (string) ($definition['label'] ?? str($key)->replace(['.', '-', '_'], ' ')->headline()->value()),
                'description' => (string) ($definition['description'] ?? ''),
                'supported_channels' => $channels,
            ];
        }
    }

    /**
     * @return array<int, array{key:string,label:string,description:string,supported_channels:array<int, string>}>
     */
    public function definitions(): array
    {
        return array_values($this->definitions);
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->definitions);
    }

    public function has(string $notificationKey): bool
    {
        return array_key_exists($notificationKey, $this->definitions);
    }
}
