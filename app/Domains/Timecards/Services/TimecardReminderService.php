<?php

namespace App\Domains\Timecards\Services;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardRequiredUser;
use App\Domains\Timecards\Notifications\MissingTimecardReminder;
use App\Domains\Timecards\Notifications\TimecardReminderNotification;
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

        $targetDate = now()->startOfDay()->subDays(max(0, $daysAfterWeekEnd));

        // Send reminders for existing draft/rejected timecards
        $sent = $this->sendExistingTimecardReminders($targetDate, $statuses);

        // Send reminders for required users with missing/unsubmitted timecards
        $sent += $this->sendMissingTimecardReminders($targetDate);

        return $sent;
    }

    /**
     * Send reminders for existing timecards with draft/rejected status.
     */
    private function sendExistingTimecardReminders(CarbonInterface $targetDate, array $statuses): int
    {
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

            // Check if user is required to submit timecards
            if (! $this->isUserTimecardRequired($user)) {
                continue;
            }

            if ($this->alreadySentReminderToday((string) $timecard->id, 'existing')) {
                continue;
            }

            $user->notify(new TimecardReminderNotification($timecard));
            $this->markReminderSentToday((string) $timecard->id, 'existing');
            $sent++;
        }

        return $sent;
    }

    /**
     * Send reminders for required users with missing submitted/approved timecards.
     */
    private function sendMissingTimecardReminders(CarbonInterface $targetDate): int
    {
        // Get all required users with reminders enabled
        $requiredUsers = TimecardRequiredUser::query()
            ->with('user')
            ->where('reminders_enabled', true)
            ->get()
            ->filter(function (TimecardRequiredUser $entry) {
                $user = $entry->user;

                if (! $user || ! $user->is_active) {
                    return false;
                }

                // Check if within effective date range
                if ($entry->effective_start_date && now() < $entry->effective_start_date) {
                    return false;
                }

                if ($entry->effective_end_date && now() > $entry->effective_end_date) {
                    return false;
                }

                return true;
            });

        $sent = 0;
        $weekStart = $targetDate->copy()->startOfWeek();

        foreach ($requiredUsers as $entry) {
            $user = $entry->user;

            // Check if user has any submitted or approved timecard for this week
            $existingValidTimecard = Timecard::query()
                ->where('user_id', $user->id)
                ->where('week_starting', $weekStart->toDateString())
                ->whereIn('status', [Timecard::STATUS_SUBMITTED, Timecard::STATUS_APPROVED])
                ->exists();

            if ($existingValidTimecard) {
                continue;
            }

            if ($this->alreadySentReminderToday($user->id, 'missing')) {
                continue;
            }

            $user->notify(new MissingTimecardReminder($weekStart));
            $this->markReminderSentToday($user->id, 'missing');
            $sent++;
        }

        return $sent;
    }

    /**
     * Check if a user is required to submit timecards.
     */
    private function isUserTimecardRequired(User $user): bool
    {
        $entry = TimecardRequiredUser::where('user_id', $user->id)->first();

        if (! $entry) {
            return false;
        }

        // Check if within effective date range
        if ($entry->effective_start_date && now() < $entry->effective_start_date) {
            return false;
        }

        if ($entry->effective_end_date && now() > $entry->effective_end_date) {
            return false;
        }

        return $entry->reminders_enabled;
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

    private function alreadySentReminderToday(string $id, string $type): bool
    {
        return Cache::has($this->cacheKey($id, $type, now()));
    }

    private function markReminderSentToday(string $id, string $type): void
    {
        Cache::put($this->cacheKey($id, $type, now()), true, now()->endOfDay());
    }

    private function cacheKey(string $id, string $type, CarbonInterface $date): string
    {
        return 'timecards.reminder_sent.'.$id.'.'.$type.'.'.$date->toDateString();
    }
}
