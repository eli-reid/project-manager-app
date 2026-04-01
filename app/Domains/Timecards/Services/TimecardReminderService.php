<?php

namespace App\Domains\Timecards\Services;

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Notifications\TimecardReminderNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class TimecardReminderService
{
    /**
     * @param  array<string, mixed>  $taskConfig
     */
    public function sendPendingReminderNotifications(array $taskConfig = []): int
    {
        $daysAfterWeekEnd = (int) ($taskConfig['days_after_week_end'] ?? 0);
        $statuses = $this->normalizeStatuses($taskConfig['statuses'] ?? [
            Timecard::STATUS_DRAFT,
            Timecard::STATUS_REJECTED,
        ]);

        $targetDate = now()->startOfDay()->subDays(max(0, $daysAfterWeekEnd));

        $timecards = Timecard::query()
            ->with('user')
            ->whereIn('status', $statuses)
            ->whereDate('week_ending', '<=', $targetDate)
            ->get();

        $sent = 0;

        foreach ($timecards as $timecard) {
            $user = $timecard->user;

            if (! $user || ! $user->is_active) {
                continue;
            }

            if ($this->alreadySentReminderToday((string) $timecard->id)) {
                continue;
            }

            $user->notify(new TimecardReminderNotification($timecard));
            $this->markReminderSentToday((string) $timecard->id);
            $sent++;
        }

        return $sent;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeStatuses(mixed $statuses): array
    {
        if (! is_array($statuses)) {
            return [Timecard::STATUS_DRAFT, Timecard::STATUS_REJECTED];
        }

        return collect($statuses)
            ->filter(fn (mixed $status): bool => is_string($status) && $status !== '')
            ->values()
            ->all();
    }

    private function alreadySentReminderToday(string $timecardId): bool
    {
        return Cache::has($this->cacheKey($timecardId, now()));
    }

    private function markReminderSentToday(string $timecardId): void
    {
        Cache::put($this->cacheKey($timecardId, now()), true, now()->endOfDay());
    }

    private function cacheKey(string $timecardId, CarbonInterface $date): string
    {
        return 'timecards.reminder_sent.'.$timecardId.'.'.$date->toDateString();
    }
}
