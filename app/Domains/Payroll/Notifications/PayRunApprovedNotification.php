<?php

namespace App\Domains\Payroll\Notifications;

use App\Core\Identity\Models\User;
use App\Core\Notification\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class PayRunApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $payRunId,
        public readonly string $approvedBy,
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
            ['mail', 'database'],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pay Run Approved — '.$this->payPeriodEnd)
            ->markdown('payroll::emails.notifications.pay-run-approved', [
                'payRunId' => $this->payRunId,
                'approvedBy' => $this->approvedBy,
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
            'approved_by' => $this->approvedBy,
            'pay_period_start' => $this->payPeriodStart,
            'pay_period_end' => $this->payPeriodEnd,
        ];
    }

    private function notificationKey(): string
    {
        return PayrollNotificationDefinitions::APPROVED;
    }
}
