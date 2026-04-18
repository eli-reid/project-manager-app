<?php

namespace App\Domains\Timecards\Services;

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Notifications\TimecardReminderDigestNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TimecardReminderService
{
    public function __construct(private readonly TimecardWeekService $timecardWeekService) {}

    /**
     * @param  array<string, mixed>  $taskConfig
     */
    public function sendPendingReminderNotifications(array $taskConfig = []): int
    {
        $daysAfterWeekEnd = (int) ($taskConfig['days_after_week_end'] ?? 0);
        $batchSize = (int) ($taskConfig['batch_size'] ?? 10);
        $batchSize = max(1, min($batchSize, 100));
        $statuses = $this->normalizeStatuses($taskConfig['statuses'] ?? [
            Timecard::STATUS_DRAFT,
            Timecard::STATUS_REJECTED,
        ]);

        $referenceDate = now()->startOfDay()->subDays(max(0, $daysAfterWeekEnd));
        $targetWeekEnding = $this->timecardWeekService->weekEndingFor($referenceDate)->toDateString();

        $timecards = Timecard::query()
            ->with('user')
            ->whereIn('status', $statuses)
            ->whereDate('week_ending', $targetWeekEnding)
            ->get();

        /** @var Collection<string, Collection<int, Timecard>> $timecardsByUser */
        $timecardsByUser = $timecards
            ->groupBy(fn (Timecard $timecard): string => (string) $timecard->user_id);

        $sent = 0;

        foreach ($timecardsByUser->chunk($batchSize) as $recipientBatch) {
            foreach ($recipientBatch as $userTimecards) {
                $user = $userTimecards->first()?->user;

                if (! $user || ! $user->is_active) {
                    continue;
                }

                if (! $this->claimReminderForToday((string) $user->id, $targetWeekEnding)) {
                    continue;
                }

                try {
                    $user->notify(new TimecardReminderDigestNotification($userTimecards->values(), $targetWeekEnding));
                    $sent++;
                } catch (\Throwable $exception) {
                    Cache::forget($this->cacheKey((string) $user->id, $targetWeekEnding, now()));

                    throw $exception;
                }
            }
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

    private function claimReminderForToday(string $userId, string $weekEndingDate): bool
    {
        return Cache::add($this->cacheKey($userId, $weekEndingDate, now()), true, now()->endOfDay());
    }

    private function cacheKey(string $userId, string $weekEndingDate, CarbonInterface $date): string
    {
        return 'timecards.reminder_sent.user.'.$userId.'.week_ending.'.$weekEndingDate.'.'.$date->toDateString();
    }
}
