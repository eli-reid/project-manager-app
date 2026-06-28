<?php 

namespace App\Core\Notification\Channels;

use App\Core\Notification\Contracts\EmailNotification;
use App\Core\Notification\Contracts\SmsNotification;
use App\Core\Notification\Contracts\PushNotification;
use App\Core\Identity\Models\User;
use Illuminate\Support\Facades\Log;


class NotificationDispatcher
{
    public function __construct(
        protected SmsChannel $sms,
        protected EmailChannel $email,
        protected PushChannel $push,
    ) {}

    public function send(object $notification, User $user): void
    {
        // SMS
        if ($notification instanceof SmsNotification) {
            $smsMessage = $notification->toSms($user);

            // If the bound SMS service is not configured (e.g. NullSmsService),
            // skip sending and do not trigger failure fallbacks.
            if (! $this->sms->isConfigured()) {
                Log::info('SMS service not configured; skipping SMS send.', ['user_id' => $user->id]);
            } else {
                $result = $this->sms->send($smsMessage);

                if (empty($result)) {
                    $this->handleSmsFailure($notification, $user, $result);
                }
            }
        }

        // Email
        if ($notification instanceof EmailNotification) {
            $emailMessage = $notification->toEmail($user);

            if (! $this->email->isConfigured()) {
                Log::info('Email service not configured; skipping email send.', ['user_id' => $user->id]);
            } else {
                $result = $this->email->send($emailMessage);

                if ($result === false) {
                    $this->handleEmailFailure($notification, $user, $result);
                }
            }
        }

        // Push
        if ($notification instanceof PushNotification) {
            $pushMessage = $notification->toPush($user);
            $result = $this->push->send($pushMessage);

            if ($result->failed()) {
                $this->handlePushFailure($notification, $user, $result);
            }
        }
    }

    protected function handleSmsFailure(
        object $notification,
        User $user,
        array $result
    ): void {
        Log::warning('SMS delivery failed', [
            'user_id' => $user->id,
            'error' => $result['error'] ?? null,
            'notification' => get_class($notification),
        ]);

        // Fallback: Push → Email
        if ($notification instanceof PushNotification) {
            $pushMessage = $notification->toPush($user);
            $this->push->send($pushMessage);
            return;
        }

        if ($notification instanceof EmailNotification) {
            $emailMessage = $notification->toEmail($user);
            $this->email->send($emailMessage);
        }
    }

    protected function handleEmailFailure(
        object $notification,
        User $user,
        bool $result
    ): void {
        Log::error('Email delivery failed', [
            'user_id' => $user->id,
            'notification' => get_class($notification),
        ]);
    }

    protected function handlePushFailure(
        object $notification,
        User $user,
        PushResult $result
    ): void {
        Log::warning('Push delivery failed', [
            'user_id' => $user->id,
            'error' => $result->error(),
            'notification' => get_class($notification),
        ]);

        // Fallback: Email
        if ($notification instanceof EmailNotification) {
            $emailMessage = $notification->toEmail($user);
            $this->email->send($emailMessage);
        }
    }
}
