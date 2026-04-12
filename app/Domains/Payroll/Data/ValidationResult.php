<?php

namespace App\Domains\Payroll\Data;

readonly class ValidationResult
{
    /**
     * @param  array<int, ValidationViolation>  $violations
     */
    public function __construct(
        public array $violations = [],
    ) {}

    public function passes(): bool
    {
        return ! $this->hasBlocks();
    }

    public function hasBlocks(): bool
    {
        foreach ($this->violations as $violation) {
            if ($violation->isBlock()) {
                return true;
            }
        }

        return false;
    }

    public function hasWarnings(): bool
    {
        foreach ($this->violations as $violation) {
            if ($violation->isWarning()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, ValidationViolation>
     */
    public function blocks(): array
    {
        return array_values(array_filter($this->violations, fn (ValidationViolation $v) => $v->isBlock()));
    }

    /**
     * @return array<int, ValidationViolation>
     */
    public function warnings(): array
    {
        return array_values(array_filter($this->violations, fn (ValidationViolation $v) => $v->isWarning()));
    }
}
