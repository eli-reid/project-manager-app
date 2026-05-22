<?php

namespace App\Domains\Timecards\Services;

use App\Core\Identity\Models\User;
use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Models\TimecardRequiredUser;
use App\Domains\Timecards\Notifications\MissingTimecardReminder;
use App\Domains\Timecards\Notifications\TimecardReminderDigestNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $ignoreDailyReminderLimit = (bool) ($taskConfig['ignore_daily_reminder_limit'] ?? false);
        $statuses = $this->normalizeStatuses($taskConfig['statuses'] ?? [
            Timecard::STATUS_DRAFT,
            Timecard::STATUS_REJECTED,
        ]);

        $targetDate = now()->startOfDay()->subDays(max(0, $daysAfterWeekEnd));
        $targetWeekEnding = $this->timecardWeekService
            ->weekEndingFor($targetDate)
            ->toDateString();

        // Send digest reminders for existing draft/rejected timecards.
        $sent = $this->sendExistingTimecardReminders($targetWeekEnding, $statuses, $batchSize, $ignoreDailyReminderLimit);

        // Send reminders for required users with missing submitted/approved timecards.
        $sent += $this->sendMissingTimecardReminders($targetWeekEnding, $batchSize, $ignoreDailyReminderLimit);

        Log::info('Timecard reminder evaluation completed.', [
            'target_week_ending' => $targetWeekEnding,
            'days_after_week_end' => $daysAfterWeekEnd,
            'batch_size' => $batchSize,
            'statuses' => $statuses,
            'ignore_daily_reminder_limit' => $ignoreDailyReminderLimit,
            'sent_count' => $sent,
        ]);

        return $sent;
    }

    /**
     * Send digest reminders for existing timecards with draft/rejected status.
     */
    private function sendExistingTimecardReminders(string $targetWeekEnding, array $statuses, int $batchSize, bool $ignoreDailyReminderLimit): int
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
        $skippedInactiveOrMissingUser = 0;
        $skippedNotRequired = 0;
        $skippedAlreadySentToday = 0;

        foreach ($timecardsByUser->chunk($batchSize) as $recipientBatch) {
            foreach ($recipientBatch as $userTimecards) {
                $user = $userTimecards->first()?->user;

                if (! $user || ! $user->is_active) {
                    $skippedInactiveOrMissingUser++;

                    continue;
                }

                if (! $this->isUserTimecardRequired($user)) {
                    $skippedNotRequired++;

                    continue;
                }

                if (! $ignoreDailyReminderLimit && $this->alreadySentReminderToday((string) $user->id, $targetWeekEnding)) {
                    $skippedAlreadySentToday++;

                    continue;
                }

                $user->notify(new TimecardReminderDigestNotification($userTimecards, $targetWeekEnding));
                $this->markReminderSentToday((string) $user->id, $targetWeekEnding);
                $sent++;
            }
        }

        Log::info('Timecard reminder existing-timecards evaluation summary.', [
            'target_week_ending' => $targetWeekEnding,
            'statuses' => $statuses,
            'existing_timecards_found' => $timecards->count(),
            'distinct_existing_users' => $timecardsByUser->count(),
            'existing_eligible_to_send' => $sent,
            'ignore_daily_reminder_limit' => $ignoreDailyReminderLimit,
            'existing_skipped_inactive_or_missing_user' => $skippedInactiveOrMissingUser,
            'existing_skipped_not_required' => $skippedNotRequired,
            'existing_skipped_already_sent_today' => $skippedAlreadySentToday,
        ]);

        return $sent;
    }

    /**
     * Send reminders for required users with missing submitted/approved timecards.
     */
    private function sendMissingTimecardReminders(string $targetWeekEnding, int $batchSize, bool $ignoreDailyReminderLimit): int
    {
        /** @var Collection<int, TimecardRequiredUser> $requiredEntries */
        $requiredEntries = TimecardRequiredUser::query()
            ->with('user')
            ->where('reminders_enabled', true)
            ->get()
            ->filter(fn (TimecardRequiredUser $entry): bool => $this->isEntryActiveForToday($entry));

        $sent = 0;
        $skippedInactiveOrMissingUser = 0;
        $skippedSubmittedOrApprovedExists = 0;
        $skippedAnyTimecardExistsForWeek = 0;
        $skippedAlreadySentToday = 0;
        $weekStart = $this->timecardWeekService
            ->normalizeWeekStart($targetWeekEnding)
            ->toDateString();

        foreach ($requiredEntries->chunk($batchSize) as $entryBatch) {
            foreach ($entryBatch as $entry) {
                $user = $entry->user;

                if (! $user || ! $user->is_active) {
                    $skippedInactiveOrMissingUser++;

                    continue;
                }

                $hasSubmittedOrApproved = Timecard::query()
                    ->where('user_id', $user->id)
                    ->whereDate('week_starting', $weekStart)
                    ->whereIn('status', [Timecard::STATUS_SUBMITTED, Timecard::STATUS_APPROVED])
                    ->exists();

                if ($hasSubmittedOrApproved) {
                    $skippedSubmittedOrApprovedExists++;

                    continue;
                }

                // If a timecard already exists for this week (draft/rejected/etc), the digest reminder path handles it.
                $hasAnyTimecardForWeek = Timecard::query()
                    ->where('user_id', $user->id)
                    ->whereDate('week_starting', $weekStart)
                    ->exists();

                if ($hasAnyTimecardForWeek) {
                    $skippedAnyTimecardExistsForWeek++;

                    continue;
                }

                if (! $ignoreDailyReminderLimit && $this->alreadySentReminderToday((string) $user->id, $targetWeekEnding)) {
                    $skippedAlreadySentToday++;

                    continue;
                }

                $user->notify(new MissingTimecardReminder(now()->parse($weekStart)));
                $this->markReminderSentToday((string) $user->id, $targetWeekEnding);
                $sent++;
            }
        }

        Log::info('Timecard reminder missing-timecard evaluation summary.', [
            'target_week_ending' => $targetWeekEnding,
            'target_week_starting' => $weekStart,
            'required_entries_active' => $requiredEntries->count(),
            'missing_eligible_to_send' => $sent,
            'ignore_daily_reminder_limit' => $ignoreDailyReminderLimit,
            'missing_skipped_inactive_or_missing_user' => $skippedInactiveOrMissingUser,
            'missing_skipped_submitted_or_approved_exists' => $skippedSubmittedOrApprovedExists,
            'missing_skipped_any_timecard_exists_for_week' => $skippedAnyTimecardExistsForWeek,
            'missing_skipped_already_sent_today' => $skippedAlreadySentToday,
        ]);

        return $sent;
    }

    private function isEntryActiveForToday(TimecardRequiredUser $entry): bool
    {
        $today = now();

        if ($entry->effective_start_date && $today->lt($entry->effective_start_date)) {
            return false;
        }

        if ($entry->effective_end_date && $today->gt($entry->effective_end_date)) {
            return false;
        }

        return true;
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

        return $entry->reminders_enabled && $this->isEntryActiveForToday($entry);
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

    private function alreadySentReminderToday(string $userId, string $weekEnding): bool
    {
        return Cache::has($this->cacheKey($userId, $weekEnding, now()));
    }

    private function markReminderSentToday(string $userId, string $weekEnding): void
    {
        Cache::put($this->cacheKey($userId, $weekEnding, now()), true, now()->endOfDay());
    }

    private function cacheKey(string $userId, string $weekEnding, CarbonInterface $date): string
    {
        return 'timecards.reminder_sent.user.'.$userId.'.week_ending.'.$weekEnding.'.'.$date->toDateString();
    }
}
