<?php

declare(strict_types=1);

namespace App\Domains\Plans\Contracts;

interface PlanRasterizerContract
{
    public function pageCount(string $absolutePdfPath): int;

    public function renderPage(
        string $absolutePdfPath,
        int $page,
        int $dpi,
        string $absoluteOutPath,
    ): void;

    public function isAvailable(): bool;

    public function name(): string;
}
