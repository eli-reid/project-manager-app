<?php

namespace App\Domains\Payroll\Notifications;

use App\Core\Identity\Models\User;
use App\Core\Notification\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class PayRunVoidedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $payRunId,
        public readonly string $payPeriodStart,
        public readonly string $payPeriodEnd,
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
            ['mail', 'database', 'sms'],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('ALERT: Pay Run Voided — '.$this->payPeriodEnd)
            ->markdown('payroll::emails.notifications.pay-run-voided', [
                'payRunId' => $this->payRunId,
                'payPeriodStart' => $this->payPeriodStart,
                'payPeriodEnd' => $this->payPeriodEnd,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->notificationKey(),
            'pay_run_id' => $this->payRunId,
            'pay_period_start' => $this->payPeriodStart,
            'pay_period_end' => $this->payPeriodEnd,
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
            'message' => 'CRITICAL: Pay run for '.$this->payPeriodEnd.' has been voided. Check admin console immediately.',
        ];
    }

    private function notificationKey(): string
    {
        return PayrollNotificationDefinitions::VOIDED;
    }
}
