<?php

declare(strict_types=1);

namespace App\Domains\Plans\Console\Commands;

use App\Domains\Plans\Services\PlanRasterizerManager;
use Illuminate\Console\Command;

final class CheckRasterizerCommand extends Command
{
    protected $signature = 'plans:rasterizer-check';

    protected $description = 'Report Plans rasterizer availability and the selected driver.';

    public function handle(PlanRasterizerManager $manager): int
    {
        $selected = $manager->getDefaultDriver();
        $rows = [];

        foreach (['poppler', 'imagick'] as $driver) {
            $rasterizer = $manager->driver($driver);
            $rows[] = [$rasterizer->name(), $rasterizer->isAvailable() ? 'available' : 'unavailable', $driver === $selected ? 'selected' : ''];
        }

        $this->table(['Driver', 'Status', 'Selection'], $rows);

        if (! in_array($selected, ['poppler', 'imagick'], true)) {
            $this->error("Configured rasterizer driver [{$selected}] is invalid.");

            return self::FAILURE;
        }

        if (! $manager->driver($selected)->isAvailable()) {
            $this->error("Configured rasterizer driver [{$selected}] is unavailable.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
