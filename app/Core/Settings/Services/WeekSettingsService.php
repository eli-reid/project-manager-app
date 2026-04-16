<?php

namespace App\Core\Settings\Services;

use App\Core\Settings\Facades\Settings;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class WeekSettingsService
{
    public function weekStartsAt(): int
    {
        return match (strtolower(Settings::get('app.week_start_day', 'sunday')->toString('sunday'))) {
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday' => Carbon::THURSDAY,
            'friday' => Carbon::FRIDAY,
            'saturday' => Carbon::SATURDAY,
            default => Carbon::SUNDAY,
        };
    }

    public function normalizeWeekStart(CarbonInterface|string $date): Carbon
    {
        return Carbon::parse($date)->startOfWeek($this->weekStartsAt());
    }

    public function weekEndFromStart(CarbonInterface|string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($this->normalizeWeekStart($date)->toDateString())
            ->addDays(6)
            ->endOfDay();
    }
}
