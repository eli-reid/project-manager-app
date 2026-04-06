<?php

namespace App\Core\Identity\Notifications;

use App\Core\Identity\Models\User;
use App\Core\Notification\Channels\SmsChannel;
use App\Core\Notification\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?string $temporaryPassword = null
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        if (is_string($this->temporaryPassword) && $this->temporaryPassword !== '') {
            return ['mail'];
        }

        return app(NotificationPreferenceService::class)->resolveChannels(
            $notifiable,
            'users.invitation',
            ['mail', 'database', SmsChannel::class],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(is_string($this->temporaryPassword) && $this->temporaryPassword !== ''
                ? 'Your Account Is Ready'
                : 'Welcome to '.config('app.name'))
            ->markdown('core-user::emails.notifications.user-invitation', [
                'loginUrl' => route('login'),
                'temporaryPassword' => $this->temporaryPassword,
                'user' => $notifiable,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => 'users.invitation',
            'message' => 'Your account has been created.',
            'url' => route('login'),
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
            'message' => 'Your account has been created. Sign in to get started.',
        ];
    }
}
