<?php

namespace App\Domains\Timecards\Services;

use App\Core\Settings\Services\WeekSettingsService;
use App\Domains\Timecards\Models\Timecard;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimecardWeekService
{
    public function __construct(private readonly WeekSettingsService $weekSettingsService) {}

    public function normalizeWeekStart(CarbonInterface|string $date): Carbon
    {
        return $this->weekSettingsService->normalizeWeekStart($date);
    }

    public function weekEndingFor(CarbonInterface|string $date): Carbon
    {
        return $this->normalizeWeekStart($date)->copy()->addDays(6);
    }

    public function currentWeekStart(): Carbon
    {
        return $this->normalizeWeekStart(now());
    }

    public function hasExistingTimecardForWeek(string $userId, CarbonInterface|string $date, ?string $ignoreTimecardId = null): bool
    {
        return $this->existingTimecardForWeek($userId, $date, $ignoreTimecardId) !== null;
    }

    public function existingTimecardForWeek(string $userId, CarbonInterface|string $date, ?string $ignoreTimecardId = null): ?Timecard
    {
        return Timecard::query()
            ->where('user_id', $userId)
            ->whereDate('week_starting', $this->normalizeWeekStart($date))
            ->when($ignoreTimecardId, fn ($query) => $query->whereKeyNot($ignoreTimecardId))
            ->first();
    }

    /**
     * @return Collection<int, array{start:string,label:string}>
     */
    public function futureWeekOptions(string $userId, int $weeksAhead = 4, bool $includePreviousWeek = false): Collection
    {
        $currentWeekStart = $this->currentWeekStart();

        $offsets = collect(range(0, max($weeksAhead - 1, 0)));

        if ($includePreviousWeek) {
            $offsets->prepend(-1);
        }

        $weekStarts = $offsets
            ->map(fn (int $offset): string => $currentWeekStart->copy()->addWeeks($offset)->toDateString());

        $existingWeekStarts = Timecard::query()
            ->where('user_id', $userId)
            ->whereDate('week_starting', '>=', $weekStarts->min())
            ->whereDate('week_starting', '<=', $weekStarts->max())
            ->pluck('week_starting')
            ->map(fn ($weekStart): string => Carbon::parse($weekStart)->toDateString())
            ->intersect($weekStarts)
            ->flip();

        return $offsets
            ->map(function (int $offset) use ($currentWeekStart, $existingWeekStarts): ?array {
                $weekStart = $currentWeekStart->copy()->addWeeks($offset);

                if ($existingWeekStarts->has($weekStart->toDateString())) {
                    return null;
                }

                return [
                    'start' => $weekStart->toDateString(),
                    'label' => $weekStart->format('M d').' - '.$weekStart->copy()->addDays(6)->format('M d, Y'),
                ];
            })
            ->filter()
            ->values();
    }
}
