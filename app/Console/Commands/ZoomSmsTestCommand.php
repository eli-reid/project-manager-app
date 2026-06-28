<?php

namespace App\Console\Commands;

use App\PlugIns\Zoom\Data\ZoomConfig;
use App\PlugIns\Zoom\Services\ZoomSmsConsentService;
use App\PlugIns\Zoom\Services\ZoomSmsService;
use App\PlugIns\Zoom\Services\ZoomTokenService;
use Illuminate\Console\Command;

class ZoomSmsTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:sms-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively test Zoom SMS sending with temporary credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Zoom SMS Test');
        $this->line('This command uses temporary values for the current run only.');
        $this->line('');

        $accountId = $this->ask('Zoom Account ID');
        $clientId = $this->ask('Zoom Client ID');
        $clientSecret = $this->secret('Zoom Client Secret');
        $fromNumber = $this->ask('Zoom SMS From Number (E.164, e.g. +12125551234)');
        $toNumber = $this->ask('Recipient phone number (E.164, e.g. +12125551234)');
        $userId = $this->ask('Zoom user ID for consent lookups', 'me');
        $campaignId = $this->ask('Zoom SMS Campaign ID for consent sync (optional)', '');
        $message = $this->ask('Message to send', 'Zoom SMS test from Laravel');
        $rawSend = $this->confirm('Bypass the consent gate and send a raw SMS?', true);

        config([
            'services.zoom.account_id' => $accountId,
            'services.zoom.client_id' => $clientId,
            'services.zoom.client_secret' => $clientSecret,
            'services.zoom.from_number' => $fromNumber,
            'services.zoom.zoom_user_id' => $userId,
            'services.zoom.sms_campaign_id' => $campaignId !== '' ? $campaignId : null,
        ]);

        app()->forgetInstance(ZoomConfig::class);
        app()->forgetInstance(ZoomTokenService::class);
        app()->forgetInstance(ZoomSmsConsentService::class);
        app()->forgetInstance(ZoomSmsService::class);

        /** @var ZoomSmsService $smsService */
        $smsService = app(ZoomSmsService::class);

        if (! $smsService->isConfigured()) {
            $this->error('Zoom SMS is not configured. Check the values you entered.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info($rawSend ? 'Sending raw SMS...' : 'Sending via consent-gated SMS flow...');

        try {
            $result = $rawSend
                ? $smsService->sendRaw($toNumber, $message)
                : $smsService->send($toNumber, new class($toNumber, $message) implements \App\Core\Notification\Contracts\SmsMessage {
                    public function __construct(private string $to, private string $body) {}
                    public function to(): string { return $this->to; }
                    public function body(): string { return $this->body; }
                    public function title(): ?string { return null; }
                    public function from(): ?string { return null; }
                });

            if ($result === []) {
                $this->warn('The message was withheld by the consent flow.');

                return self::SUCCESS;
            }

            $this->info('Zoom SMS send succeeded.');
            $this->line('Message ID: '.($result['message_id'] ?? ''));
            $this->line('Session ID: '.($result['session_id'] ?? ''));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Zoom SMS send failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
