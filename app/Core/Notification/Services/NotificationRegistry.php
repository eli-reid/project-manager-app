<?php

namespace App\Core\Notification\Services;

use Illuminate\Support\Facades\Log;

final class NotificationRegistry
{
    /**
     * @var array<string, array{key:string,label:string,description:string}>
     */
    private array $definitions = [];

    /**
     * Register notification definitions. We intentionally do not track supported
     * channels here — channel selection is controlled by user settings elsewhere.
     *
     * @param  array<int, array{key:string,label?:string,description?:string}>  $definitions
     */
    public function registerDefinitions(array $definitions): void
    {
        foreach ($definitions as $definition) {
            $key = (string) ($definition['key'] ?? '');

            if ($key === '') {
                continue;
            }

            if (array_key_exists($key, $this->definitions)) {
                Log::warning('NotificationRegistry: duplicate key ignored during registerDefinitions.', [
                    'key' => $key,
                    'existing_label' => $this->definitions[$key]['label'],
                ]);

                continue;
            }

            $this->definitions[$key] = [
                'key' => $key,
                'label' => (string) ($definition['label'] ?? str($key)->replace(['.', '-', '_'], ' ')->headline()->value()),
                'description' => (string) ($definition['description'] ?? ''),
            ];
        }
    }

    /**
     * @return array<int, array{key:string,label:string,description:string}>
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

    /**
     * Get a single definition by key, or null if not found.
     *
     * @return array{key:string,label:string,description:string}|null
     */
    public function getDefinition(string $key): ?array
    {
        return $this->definitions[$key] ?? null;
    }
}
