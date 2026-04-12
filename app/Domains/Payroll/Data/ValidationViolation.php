<?php

namespace App\Domains\Payroll\Data;

use App\Domains\Payroll\Enums\ValidationSeverity;

readonly class ValidationViolation
{
    public function __construct(
        public string $ruleId,
        public ValidationSeverity $severity,
        public string $message,
    ) {}

    public function isBlock(): bool
    {
        return $this->severity === ValidationSeverity::Block;
    }

    public function isWarning(): bool
    {
        return $this->severity === ValidationSeverity::Warning;
    }
}
