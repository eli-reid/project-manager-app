<?php

declare(strict_types=1);

namespace App\Domains\Projects\Contracts;

use Carbon\CarbonInterface;

interface ProjectContextInterface
{
    public function id(): string;

    public function name(): string;

    public function number(): ?string;

    public function status(): string;

    public function startsAt(): ?CarbonInterface;

    public function endsAt(): ?CarbonInterface;

    public function isActive(): bool;

}
