<?php

namespace App\PlugIns\Services;

use Illuminate\Support\Facades\Log;

class PluginDataRegistry
{
    /**
     * @var array<string, array{key:string,resolver:callable,allowed_callers:list<string>,required_ability:string}>
     */
    private array $definitions = [];

    /**
     * @param  list<string>  $allowedCallers
     */
    public function register(
        string $key,
        callable $resolver,
        array $allowedCallers = ['*'],
        string $requiredAbility = '',
    ): void {
        if ($key === '') {
            return;
        }

        if (array_key_exists($key, $this->definitions)) {
            Log::warning('PluginDataRegistry: duplicate key ignored during register.', [
                'key' => $key,
            ]);

            return;
        }

        $normalizedCallers = collect($allowedCallers)
            ->filter(fn (mixed $caller): bool => is_string($caller) && $caller !== '')
            ->unique()
            ->values()
            ->all();

        $this->definitions[$key] = [
            'key' => $key,
            'resolver' => $resolver,
            'allowed_callers' => $normalizedCallers === [] ? ['*'] : $normalizedCallers,
            'required_ability' => $requiredAbility,
        ];
    }

    /**
     * @return array{key:string,resolver:callable,allowed_callers:list<string>,required_ability:string}|null
     */
    public function find(string $key): ?array
    {
        return $this->definitions[$key] ?? null;
    }
}
