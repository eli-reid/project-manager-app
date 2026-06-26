<?php

namespace App\Domains\Payroll\Services;

use App\Core\Settings\Facades\Settings;

class TaxWithholdingService
{
    /**
     * @var array<int, array{up_to:float|null,rate:float}>
     */
    private const DEFAULT_FEDERAL_TABLE = [
        ['up_to' => null, 'rate' => 0.12],
    ];

    /**
     * @var array<int, array{up_to:float|null,rate:float}>
     */
    private const DEFAULT_STATE_TABLE = [
        ['up_to' => null, 'rate' => 0.05],
    ];

    /**
     * @var array<int, array{up_to:float|null,rate:float}>
     */
    private const DEFAULT_LOCAL_TABLE = [
        ['up_to' => null, 'rate' => 0.01],
    ];

    /**
     * @return array{federal:float,state:float,local:float,social_security:float,medicare:float,total:float}
     */
    public function calculate(float $taxableGross): array
    {
        $taxableGross = round(max(0.0, $taxableGross), 2);

        $federal = $this->calculateFromTable($taxableGross, $this->tableSetting('payroll.tax_withholding.federal_table', self::DEFAULT_FEDERAL_TABLE));
        $state = $this->calculateFromTable($taxableGross, $this->tableSetting('payroll.tax_withholding.state_table', self::DEFAULT_STATE_TABLE));
        $local = $this->calculateFromTable($taxableGross, $this->tableSetting('payroll.tax_withholding.local_table', self::DEFAULT_LOCAL_TABLE));
        $socialSecurity = round($taxableGross * $this->rateSetting('payroll.tax_withholding.social_security_rate', 0.062), 2);
        $medicare = round($taxableGross * $this->rateSetting('payroll.tax_withholding.medicare_rate', 0.0145), 2);

        return [
            'federal' => $federal,
            'state' => $state,
            'local' => $local,
            'social_security' => $socialSecurity,
            'medicare' => $medicare,
            'total' => round($federal + $state + $local + $socialSecurity + $medicare, 2),
        ];
    }

    /**
     * @param  array<int, array{up_to:float|null,rate:float}>  $table
     */
    private function calculateFromTable(float $taxableGross, array $table): float
    {
        if ($taxableGross <= 0.0 || $table === []) {
            return 0.0;
        }

        $total = 0.0;
        $lowerBound = 0.0;

        foreach ($table as $bracket) {
            $upperBound = $bracket['up_to'];

            if ($upperBound === null) {
                $taxableAmount = max(0.0, $taxableGross - $lowerBound);
                $total += $taxableAmount * $bracket['rate'];

                break;
            }

            $taxableAmount = max(0.0, min($taxableGross, $upperBound) - $lowerBound);
            $total += $taxableAmount * $bracket['rate'];
            $lowerBound = max($lowerBound, $upperBound);

            if ($taxableGross <= $upperBound) {
                break;
            }
        }

        return round(max(0.0, $total), 2);
    }

    /**
     * @param  array<int, array{up_to:float|null,rate:float}>  $default
     * @return array<int, array{up_to:float|null,rate:float}>
     */
    private function tableSetting(string $key, array $default): array
    {
        $raw = Settings::get($key, json_encode($default, JSON_THROW_ON_ERROR))->raw();

        if (! is_string($raw) || trim($raw) === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return $default;
        }

        $normalized = collect($decoded)
            ->map(function (mixed $row): ?array {
                if (! is_array($row) || ! array_key_exists('rate', $row)) {
                    return null;
                }

                $rate = $this->normalizeRate($row['rate']);

                if ($rate < 0.0) {
                    return null;
                }

                $upTo = $row['up_to'] ?? null;
                $upToValue = is_numeric($upTo) && (float) $upTo > 0.0
                    ? (float) $upTo
                    : null;

                return [
                    'up_to' => $upToValue,
                    'rate' => $rate,
                ];
            })
            ->filter()
            ->sortBy(function (array $row): float {
                return $row['up_to'] ?? INF;
            })
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : $default;
    }

    private function rateSetting(string $key, float $default): float
    {
        $raw = Settings::get($key, $default)->raw();
        $normalized = $this->normalizeRate($raw);

        return $normalized >= 0.0 ? $normalized : $default;
    }

    private function normalizeRate(mixed $value): float
    {
        if (! is_numeric($value)) {
            return -1.0;
        }

        $numeric = (float) $value;

        if ($numeric > 1.0 && $numeric <= 100.0) {
            return $numeric / 100.0;
        }

        return $numeric;
    }
}
