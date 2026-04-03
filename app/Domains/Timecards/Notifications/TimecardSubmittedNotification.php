<?php

namespace App\Domains\Timecards\Notifications;

use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Services\NotificationPreferenceService;
use App\Core\User\Models\User;
use App\Domains\Timecards\Models\Timecard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class TimecardSubmittedNotification extends Notification implements ShouldQueue
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
            ->subject('Timecard Submitted for Review')
            ->markdown('timecards::emails.notifications.timecard-submitted', [
                'timecard' => $this->timecard,
                'employeeName' => $this->timecard->user?->first_name.' '.$this->timecard->user?->last_name,
                'reviewUrl' => route('admin.timecards.show', $this->timecard),
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
            'employee_name' => trim(($this->timecard->user?->first_name ?? '').' '.($this->timecard->user?->last_name ?? '')),
            'week_starting' => optional($this->timecard->week_starting)->toDateString(),
            'week_ending' => optional($this->timecard->week_ending)->toDateString(),
            'url' => route('admin.timecards.show', $this->timecard),
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
            'message' => 'A timecard was submitted and is ready for review.',
        ];
    }

    private function notificationKey(): string
    {
        return TimecardNotificationDefinitions::SUBMITTED;
    }
}
