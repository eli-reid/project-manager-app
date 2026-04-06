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
use Illuminate\Queue\SerializesModels;

class TimecardApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Timecard $timecard,
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
            ['mail', 'database', SmsChannel::class],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Timecard Was Approved')
            ->markdown('timecards::emails.notifications.timecard-approved', [
                'timecard' => $this->timecard,
                'showUrl' => route('timecards.show', $this->timecard),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->notificationKey(),
            'timecard_id' => (string) $this->timecard->id,
            'status' => (string) $this->timecard->status,
            'week_starting' => optional($this->timecard->week_starting)->toDateString(),
            'week_ending' => optional($this->timecard->week_ending)->toDateString(),
            'url' => route('timecards.show', $this->timecard),
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

        return [
            'to' => $phone,
            'message' => 'Your timecard was approved.',
        ];
    }

    private function notificationKey(): string
    {
        return TimecardNotificationDefinitions::APPROVED;
    }
}
