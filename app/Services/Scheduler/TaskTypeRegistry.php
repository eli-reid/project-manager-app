<?php
namespace App\Services\Scheduler;

class TaskTypeRegistry
{
    protected array $types = [];

    public function register(string $featureType, string $class): void
    {
        $this->types[$featureType] = $class;
    }

    public function resolve(string $featureType): ?string
    {
        return $this->types[$featureType] ?? null;
    }
}