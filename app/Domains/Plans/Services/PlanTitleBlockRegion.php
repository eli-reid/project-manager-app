<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

use App\Core\Settings\Facades\Settings;

final class PlanTitleBlockRegion
{
    /**
     * @return array{x:float,y:float,width:float,height:float}|null
     */
    public function resolve(): ?array
    {
        $value = trim(Settings::get('plans.title_block_region', config('plans.title_block_region', 'right-strip'))->toString());

        return match ($value) {
            '', 'full-page' => null,
            'right-strip' => ['x' => 0.78, 'y' => 0.0, 'width' => 0.22, 'height' => 1.0],
            'bottom-right' => ['x' => 0.78, 'y' => 0.55, 'width' => 0.22, 'height' => 0.45],
            default => $this->fromJson($value),
        };
    }

    /**
     * @return array{x:float,y:float,width:float,height:float}|null
     */
    private function fromJson(string $value): ?array
    {
        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return null;
        }

        foreach (['x', 'y', 'width', 'height'] as $key) {
            if (! is_numeric($decoded[$key] ?? null)) {
                return null;
            }
        }

        return [
            'x' => $this->clamp((float) $decoded['x']),
            'y' => $this->clamp((float) $decoded['y']),
            'width' => $this->clamp((float) $decoded['width']),
            'height' => $this->clamp((float) $decoded['height']),
        ];
    }

    private function clamp(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}
