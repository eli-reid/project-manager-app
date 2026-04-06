<?php

namespace App\Core\Cpanel\Jobs;

use App\Core\Cpanel\Models\CachedEmailAccount;
use App\Core\Cpanel\Services\CpanelService;
use App\Core\Identity\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncEmailAccountsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue((string) config('services.cpanel.queue_name', 'default'));
    }

    public function handle(CpanelService $cpanelService): void
    {
        Log::info('Starting cPanel email account sync job.');

        if (! $cpanelService->isConfigured()) {
            Log::warning('Skipping email account sync because cPanel is not configured.');

            return;
        }

        $result = $cpanelService->listEmailAccounts();

        if (! ($result['success'] ?? false)) {
            Log::warning('Unable to sync email accounts from cPanel.', [
                'message' => $result['message'] ?? 'Unknown cPanel sync error.',
            ]);

            return;
        }

        $syncedAt = now();

        $processedCount = 0;

        foreach (($result['emails'] ?? []) as $emailAccount) {
            $email = strtolower(trim((string) ($emailAccount['email'] ?? '')));

            if ($email === '') {
                continue;
            }

            $domain = trim((string) ($emailAccount['domain'] ?? ''));
            if ($domain === '' && str_contains($email, '@')) {
                $domain = (string) str($email)->after('@');
            }

            $quota = (int) ($emailAccount['quota'] ?? 0);
            $usage = (int) ($emailAccount['usage'] ?? 0);
            $usagePercentage = (float) ($emailAccount['usage_percentage'] ?? 0.0);

            if ($usagePercentage <= 0 && $quota > 0) {
                $usagePercentage = round(($usage / $quota) * 100, 2);
            }

            $userId = User::query()
                ->where('company_email', $email)
                ->value('id');

            CachedEmailAccount::query()->updateOrCreate(
                ['email' => $email],
                [
                    'domain' => $domain !== '' ? $domain : null,
                    'suspended' => (bool) ($emailAccount['suspended'] ?? false),
                    'quota' => max($quota, 0),
                    'usage' => max($usage, 0),
                    'usage_percentage' => max(min($usagePercentage, 100), 0),
                    'raw_data' => $emailAccount,
                    'user_id' => $userId,
                    'last_synced_at' => $syncedAt,
                    'sync_failed' => false,
                    'sync_error' => null,
                ]
            );

            $processedCount++;
        }

        Log::info('Completed cPanel email account sync job.', [
            'remote_count' => (int) ($result['count'] ?? 0),
            'processed_count' => $processedCount,
        ]);
    }
}
