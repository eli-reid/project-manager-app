<?php

namespace App\Domains\Timecards\Notifications;

use App\Core\Identity\Models\User;
use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Services\NotificationPreferenceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class MissingTimecardReminder extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Carbon $weekStarting,
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
        $weekEnding = $this->weekStarting->copy()->addDays(6);

        return (new MailMessage)
            ->subject('Missing Timecard - Action Required')
            ->markdown('timecards::emails.notifications.missing-timecard-reminder', [
                'weekStarting' => $this->weekStarting,
                'weekEnding' => $weekEnding,
                'createUrl' => route('timecards.create'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $weekEnding = $this->weekStarting->copy()->addDays(6);

        return [
            'key' => $this->notificationKey(),
            'week_starting' => $this->weekStarting->toDateString(),
            'week_ending' => $weekEnding->toDateString(),
            'url' => route('timecards.create'),
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
            'message' => 'Reminder: You have not submitted a timecard for '.$this->weekStarting->toFormattedDateString().' week. Please submit now.',
        ];
    }

    private function notificationKey(): string
    {
        return TimecardNotificationDefinitions::MISSING_REMINDER;
    }
}
