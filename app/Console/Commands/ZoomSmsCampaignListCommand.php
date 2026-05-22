<?php

namespace App\Console\Commands;

use App\Core\Zoom\Data\ZoomConfig;
use App\Core\Zoom\Services\ZoomSmsConsentService;
use App\Core\Zoom\Services\ZoomTokenService;
use Illuminate\Console\Command;

class ZoomSmsCampaignListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:sms-campaign-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively list Zoom SMS campaign phone-number opt statuses';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Zoom SMS Campaign Opt-Status List');
        $this->line('This command uses temporary values for the current run only.');
        $this->line('');

        $accountId = $this->ask('Zoom Account ID');
        $clientId = $this->ask('Zoom Client ID');
        $clientSecret = $this->secret('Zoom Client Secret');
        $campaignId = $this->ask('Zoom SMS Campaign ID');
        $pageSize = (int) $this->ask('Page size', '30');
        $nextPageToken = $this->ask('Next page token (optional)', '');

        config([
            'services.zoom.account_id' => $accountId,
            'services.zoom.client_id' => $clientId,
            'services.zoom.client_secret' => $clientSecret,
            'services.zoom.sms_campaign_id' => $campaignId !== '' ? $campaignId : null,
        ]);

        app()->forgetInstance(ZoomConfig::class);
        app()->forgetInstance(ZoomTokenService::class);
        app()->forgetInstance(ZoomSmsConsentService::class);

        /** @var ZoomSmsConsentService $consentService */
        $consentService = app(ZoomSmsConsentService::class);

        $this->newLine();
        $this->info('Fetching campaign phone number opt statuses...');

        try {
            $result = $consentService->listCampaignPhoneNumberOptStatuses(
                pageSize: max(1, $pageSize),
                nextPageToken: $nextPageToken !== '' ? $nextPageToken : null,
            );

            /** @var array<int, array<string, mixed>> $rows */
            $rows = $result['phone_number_campaign_opt_statuses'] ?? [];

            if ($rows === []) {
                $this->warn('No phone numbers were returned for this campaign page.');
            } else {
                $tableRows = array_map(static function (array $row): array {
                    return [
                        (string) ($row['consumer_phone_number'] ?? ''),
                        (string) ($row['zoom_phone_user_number'] ?? ''),
                        (string) ($row['opt_status'] ?? ''),
                        (string) ($row['opt_in_status'] ?? ''),
                    ];
                }, $rows);

                $this->table(
                    ['consumer_phone_number', 'zoom_phone_user_number', 'opt_status', 'opt_in_status'],
                    $tableRows,
                );
            }

            $returnedToken = (string) ($result['next_page_token'] ?? '');

            if ($returnedToken !== '') {
                $this->line('Next page token: '.$returnedToken);
            } else {
                $this->line('Next page token: (none)');
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Zoom SMS campaign list failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
