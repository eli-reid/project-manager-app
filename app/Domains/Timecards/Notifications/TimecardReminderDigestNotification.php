<?php

namespace App\Domains\Timecards\Notifications;

use App\Core\Identity\Models\User;
use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Services\NotificationPreferenceService;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TimecardReminderDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, Timecard>  $timecards
     */
    public function __construct(
        public readonly Collection $timecards,
        public readonly string $weekEnding,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        return app(NotificationPreferenceService::class)->resolveChannels(
            $notifiable,
            $this->notificationKey(),
            ['mail', 'database', SmsChannel::class, WebPushChannel::class],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $primaryTimecard = $this->timecards->first();

        return (new MailMessage)
            ->subject('Timecard Reminder')
            ->markdown('timecards::emails.notifications.timecard-reminder-digest', [
                'timecards' => $this->timecards,
                'weekEnding' => $this->weekEnding,
                'showUrl' => $primaryTimecard ? route('timecards.show', $primaryTimecard) : route('timecards.index'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->notificationKey(),
            'week_ending' => $this->weekEnding,
            'timecard_count' => $this->timecards->count(),
            'timecard_ids' => $this->timecards->map(fn (Timecard $timecard): string => (string) $timecard->id)->values()->all(),
            'url' => route('timecards.index'),
        ];
    }

    /**
     * @return array{to:string|null,message:string}|null
     */
    public function toSms(object $notifiable): ?array
    {
        $phone = $notifiable->phone ?? null;

        if (! is_string($phone) || $phone === '') {
            return null;
        }

        $count = $this->timecards->count();

        return [
            'to' => $phone,
            'message' => "Reminder: you have {$count} pending timecard(s) for week ending {$this->weekEnding}. Please submit your timecard.",
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $count = $this->timecards->count();

        return (new WebPushMessage)
            ->title('Timecard reminder')
            ->body("You have {$count} pending timecard(s) for week ending {$this->weekEnding}.")
            ->icon('/icon-192.png')
            ->badge('/icon-192.png')
            ->tag('timecard-reminder-'.$this->weekEnding)
            ->data([
                'url' => route('timecards.index'),
                'key' => $this->notificationKey(),
                'week_ending' => $this->weekEnding,
                'timecard_count' => $count,
            ]);
    }

    private function notificationKey(): string
    {
        return TimecardNotificationDefinitions::REMINDER;
    }
}
