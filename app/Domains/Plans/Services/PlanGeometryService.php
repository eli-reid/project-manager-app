<?php

declare(strict_types=1);

namespace App\Domains\Plans\Services;

final class PlanGeometryService
{
    /**
     * @param  array<string, mixed>  $geometry
     * @return array<string, mixed>
     */
    public function normalize(array $geometry): array
    {
        $normalized = $geometry;

        if (isset($geometry['points']) && is_array($geometry['points'])) {
            $normalized['points'] = array_map(
                fn (mixed $point): array => [
                    max(0, min(1, (float) ($point[0] ?? 0))),
                    max(0, min(1, (float) ($point[1] ?? 0))),
                ],
                $geometry['points'],
            );
        }

        foreach (['x', 'y', 'width', 'height', 'rx', 'ry'] as $key) {
            if (array_key_exists($key, $geometry)) {
                $normalized[$key] = max(0, min(1, (float) $geometry[$key]));
            }
        }

        return $normalized;
    }
}
