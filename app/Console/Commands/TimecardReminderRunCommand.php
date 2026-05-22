<?php

namespace App\Console\Commands;

use App\Domains\Timecards\Models\Timecard;
use App\Domains\Timecards\Services\TimecardReminderService;
use Illuminate\Console\Command;

class TimecardReminderRunCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'timecards:reminders:run
        {--days-after-week-end=0 : Days after week ending to target}
        {--batch-size=10 : Reminder batch size (1-100)}
        {--statuses=draft,rejected : Comma-separated statuses for existing timecards}
        {--ignore-daily-reminder-limit : Bypass same-day reminder dedupe cache and force resend}';

    /**
     * @var string
     */
    protected $description = 'Run timecard reminders immediately for testing without waiting for scheduler due windows';

    public function handle(TimecardReminderService $reminderService): int
    {
        $daysAfterWeekEnd = max(0, (int) $this->option('days-after-week-end'));
        $batchSize = max(1, min(100, (int) $this->option('batch-size')));
        $statuses = $this->parseStatuses((string) $this->option('statuses'));
        $ignoreDailyReminderLimit = (bool) $this->option('ignore-daily-reminder-limit');

        $this->info('Running timecard reminders now...');

        if ($ignoreDailyReminderLimit) {
            $this->warn('Daily reminder dedupe cache is being bypassed for this run.');
        }

        $taskConfig = [
            'days_after_week_end' => $daysAfterWeekEnd,
            'batch_size' => $batchSize,
            'statuses' => $statuses,
            'ignore_daily_reminder_limit' => $ignoreDailyReminderLimit,
        ];

        try {
            $sentCount = $reminderService->sendPendingReminderNotifications($taskConfig);
        } catch (\Throwable $exception) {
            $this->error('Timecard reminder run failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Timecard reminders run completed.');
        $this->line('Sent reminders: '.$sentCount);

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function parseStatuses(string $statuses): array
    {
        $allowedStatuses = [
            Timecard::STATUS_DRAFT,
            Timecard::STATUS_REJECTED,
            Timecard::STATUS_SUBMITTED,
            Timecard::STATUS_APPROVED,
        ];

        $parsed = collect(explode(',', $statuses))
            ->map(fn (string $status): string => strtolower(trim($status)))
            ->filter(fn (string $status): bool => in_array($status, $allowedStatuses, true))
            ->values()
            ->all();

        if ($parsed === []) {
            return [
                Timecard::STATUS_DRAFT,
                Timecard::STATUS_REJECTED,
            ];
        }

        return $parsed;
    }
}
