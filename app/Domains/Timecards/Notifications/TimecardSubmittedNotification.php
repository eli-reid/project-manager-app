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
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

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
            ['mail', 'database', SmsChannel::class, WebPushChannel::class],
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

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        $employeeName = trim(($this->timecard->user?->first_name ?? '').' '.($this->timecard->user?->last_name ?? ''));

        return (new WebPushMessage)
            ->title('Timecard submitted')
            ->body(($employeeName !== '' ? $employeeName : 'A team member').' submitted a timecard for review.')
            ->icon('/icon-192.png')
            ->badge('/icon-192.png')
            ->tag('timecard-submitted-'.(string) $this->timecard->id)
            ->data([
                'url' => route('admin.timecards.show', $this->timecard),
                'key' => $this->notificationKey(),
                'timecard_id' => (string) $this->timecard->id,
                'employee_name' => $employeeName,
            ]);
    }

    private function notificationKey(): string
    {
        return TimecardNotificationDefinitions::SUBMITTED;
    }
}
