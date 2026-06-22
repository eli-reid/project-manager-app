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

class PayStubAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $payPeriodEnd,
        public readonly string $netPay,
        public readonly string $grossPay,
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
            ->subject('Your Pay Stub is Ready — '.$this->payPeriodEnd)
            ->markdown('payroll::emails.notifications.pay-stub-available', [
                'payPeriodEnd' => $this->payPeriodEnd,
                'netPay' => $this->netPay,
                'grossPay' => $this->grossPay,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->notificationKey(),
            'pay_period_end' => $this->payPeriodEnd,
            'net_pay' => $this->netPay,
            'gross_pay' => $this->grossPay,
        ];
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pay stub available')
            ->body('Your pay stub for '.$this->payPeriodEnd.' is ready.')
            ->icon('/icon-192.png')
            ->badge('/icon-192.png')
            ->tag('payroll-pay-stub-'.$this->payPeriodEnd)
            ->data([
                'key' => $this->notificationKey(),
                'pay_period_end' => $this->payPeriodEnd,
                'net_pay' => $this->netPay,
                'gross_pay' => $this->grossPay,
            ]);
    }

    private function notificationKey(): string
    {
        return PayrollNotificationDefinitions::PAY_STUB_AVAILABLE;
    }
}
