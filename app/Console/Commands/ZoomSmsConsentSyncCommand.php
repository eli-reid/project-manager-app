<?php

namespace App\Console\Commands;

use App\Core\Zoom\Enums\SmsConsentStatus;
use App\Core\Zoom\Services\ZoomSmsConsentService;
use Illuminate\Console\Command;

class ZoomSmsConsentSyncCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'zoom:sms-consent-sync
        {--phone= : Sync one phone number from Zoom user/campaign opt-status API}
        {--page-size=100 : Page size for campaign-wide sync mode}
        {--max-pages=10 : Maximum pages to sync in campaign-wide mode}';

    /**
     * @var string
     */
    protected $description = 'Sync Zoom SMS consent statuses into local zoom_sms_consents table';

    public function handle(ZoomSmsConsentService $consentService): int
    {
        $phone = trim((string) $this->option('phone'));

        try {
            if ($phone !== '') {
                $status = $consentService->syncFromZoom($phone);

                $this->info('Single-number Zoom consent sync complete.');
                $this->line('Phone: '.$phone);
                $this->line('Status: '.$this->statusLabel($status));

                return self::SUCCESS;
            }

            $pageSize = max(1, (int) $this->option('page-size'));
            $maxPages = max(1, (int) $this->option('max-pages'));

            $this->info('Starting campaign-wide Zoom consent sync...');

            $result = $consentService->syncCampaignConsentStatuses(
                pageSize: $pageSize,
                maxPages: $maxPages,
            );

            $this->info('Campaign-wide Zoom consent sync complete.');
            $this->line('Processed: '.(int) ($result['processed'] ?? 0));
            $this->line('Opted in: '.(int) ($result['opted_in'] ?? 0));
            $this->line('Opted out: '.(int) ($result['opted_out'] ?? 0));
            $this->line('Unknown/skipped: '.(int) ($result['unknown'] ?? 0));
            $this->line('Next page token: '.((string) ($result['next_page_token'] ?? '') ?: '(none)'));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Zoom consent sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function statusLabel(?SmsConsentStatus $status): string
    {
        return match ($status) {
            SmsConsentStatus::OptedIn => 'opt_in',
            SmsConsentStatus::OptedOut => 'opt_out',
            SmsConsentStatus::Pending => 'pending',
            default => 'unknown',
        };
    }
}
