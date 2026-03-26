<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daily_reports')) {
            return;
        }

        DB::table('daily_reports')
            ->select(['id', 'work_performed'])
            ->orderBy('id')
            ->chunk(100, function (Collection $reports): void {
                foreach ($reports as $report) {
                    $normalizedItems = $this->normalizeItems($report->work_performed);
                    $totals = $this->calculateTotals($normalizedItems);

                    DB::table('daily_reports')
                        ->where('id', $report->id)
                        ->update([
                            'work_performed' => json_encode($normalizedItems),
                            'total_regular_hours' => $totals['regular'],
                            'total_overtime_hours' => $totals['overtime'],
                            'total_hours' => $totals['regular'] + $totals['overtime'],
                        ]);
                }
            });
    }

    public function down(): void {}

    /**
     * @return array<int, array{description:string,hours:?float,employees:array<int, string>,is_overtime:bool}>
     */
    private function normalizeItems(mixed $workPerformed): array
    {
        if (is_string($workPerformed)) {
            $decoded = json_decode($workPerformed, true);
            $items = is_array($decoded) ? $decoded : [];
        } elseif (is_array($workPerformed)) {
            $items = $workPerformed;
        } else {
            $items = [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $description = trim($item);

                if ($description === '') {
                    continue;
                }

                $normalized[] = [
                    'description' => $description,
                    'hours' => null,
                    'employees' => [],
                    'is_overtime' => false,
                ];

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $description = trim((string) ($item['description'] ?? ''));

            if ($description === '') {
                continue;
            }

            $hours = isset($item['hours']) && is_numeric($item['hours'])
                ? round((float) $item['hours'], 2)
                : null;

            $employees = array_values(
                array_filter(
                    array_map(
                        fn (mixed $employee): string => trim((string) $employee),
                        is_array($item['employees'] ?? null) ? $item['employees'] : []
                    ),
                    fn (string $employee): bool => $employee !== ''
                )
            );

            $normalized[] = [
                'description' => $description,
                'hours' => $hours,
                'employees' => $employees,
                'is_overtime' => (bool) ($item['is_overtime'] ?? false),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{description:string,hours:?float,employees:array<int, string>,is_overtime:bool}>  $items
     * @return array{regular:float,overtime:float}
     */
    private function calculateTotals(array $items): array
    {
        $regularHours = 0.0;
        $overtimeHours = 0.0;

        foreach ($items as $item) {
            if ($item['hours'] === null || $item['hours'] <= 0) {
                continue;
            }

            if ($item['is_overtime']) {
                $overtimeHours += $item['hours'];
            } else {
                $regularHours += $item['hours'];
            }
        }

        return [
            'regular' => round($regularHours, 2),
            'overtime' => round($overtimeHours, 2),
        ];
    }
};
