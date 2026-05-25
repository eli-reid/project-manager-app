<?php

namespace App\Domains\Payroll\Notifications;

use App\Core\Identity\Models\User;
use App\Core\Notification\Services\NotificationPreferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PayRunFinalizedNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $payRunId,
        public readonly string $payPeriodStart,
        public readonly string $payPeriodEnd,
        public readonly string $payDate,
        public readonly int $employeeCount,
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
            ['mail', 'database', WebPushChannel::class],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pay Run Finalized — '.$this->payPeriodEnd)
            ->markdown('payroll::emails.notifications.pay-run-finalized', [
                'payRunId' => $this->payRunId,
                'payPeriodStart' => $this->payPeriodStart,
                'payPeriodEnd' => $this->payPeriodEnd,
                'payDate' => $this->payDate,
                'employeeCount' => $this->employeeCount,
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
            'pay_date' => $this->payDate,
            'employee_count' => $this->employeeCount,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pay run finalized')
            ->body('Pay run '.$this->payRunId.' was finalized for '.$this->employeeCount.' employee(s).')
            ->icon('/icon-192.png')
            ->badge('/icon-192.png')
            ->tag('payroll-pay-run-finalized-'.$this->payRunId)
            ->data([
                'key' => $this->notificationKey(),
                'pay_run_id' => $this->payRunId,
                'pay_period_start' => $this->payPeriodStart,
                'pay_period_end' => $this->payPeriodEnd,
                'pay_date' => $this->payDate,
                'employee_count' => $this->employeeCount,
            ]);
    }

    private function notificationKey(): string
    {
        return PayrollNotificationDefinitions::FINALIZED;
    }
}
