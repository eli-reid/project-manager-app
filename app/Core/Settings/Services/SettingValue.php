<?php

namespace App\Core\Settings\Services;

class SettingValue
{
    public function __construct(private mixed $value) {}

    public function raw(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }

    public function toInt(int $default = 0): int
    {
        if (is_int($this->value)) {
            return $this->value;
        }

        if (is_numeric($this->value)) {
            return (int) $this->value;
        }

        return $default;
    }

    public function toBool(bool $default = false): bool
    {
        if (is_bool($this->value)) {
            return $this->value;
        }

        if (is_int($this->value)) {
            return match ($this->value) {
                1 => true,
                0 => false,
                default => $default,
            };
        }

        if (is_string($this->value)) {
            $normalized = strtolower(trim($this->value));

            return match ($normalized) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off' => false,
                default => $default,
            };
        }

        return $default;
    }

    public function toString(string $default = ''): string
    {
        if (is_string($this->value)) {
            $normalized = trim($this->value);

            return $normalized !== '' ? $normalized : $default;
        }

        if (is_scalar($this->value)) {
            return (string) $this->value;
        }

        return $default;
    }

    public function toNullableString(): ?string
    {
        if (! is_string($this->value)) {
            return null;
        }

        $normalized = trim($this->value);

        return $normalized !== '' ? $normalized : null;
    }
}
