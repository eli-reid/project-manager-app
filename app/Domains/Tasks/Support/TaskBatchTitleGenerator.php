<?php

namespace App\Domains\Tasks\Support;

class TaskBatchTitleGenerator
{
    /**
     * @return array<int, string>
     */
    public function generate(string $title, int $count, int $startingNumber): array
    {
        if ($count === 1) {
            return [$title];
        }

        if (preg_match('/^(.*?)(\d+)([^\d]*)$/', $title, $matches) === 1) {
            $prefix = $matches[1];
            $number = $matches[2];
            $suffix = $matches[3];
            $baseNumber = (int) $number;
            $padding = strlen($number);

            return collect(range(0, $count - 1))
                ->map(fn (int $offset): string => $prefix.str_pad((string) ($baseNumber + $offset), $padding, '0', STR_PAD_LEFT).$suffix)
                ->all();
        }

        return collect(range(0, $count - 1))
            ->map(fn (int $offset): string => sprintf('%s %d', $title, $startingNumber + $offset))
            ->all();
    }
}
