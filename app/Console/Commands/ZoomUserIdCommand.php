<?php

namespace App\Console\Commands;

use App\PlugIns\Zoom\Data\ZoomConfig;
use App\PlugIns\Zoom\Services\ZoomTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ZoomUserIdCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'zoom:user-id
        {--json : Print full /users/me response JSON}';

    /**
     * @var string
     */
    protected $description = 'Fetch Zoom users/me and print the user ID for consent lookups';

    public function handle(ZoomConfig $config, ZoomTokenService $tokenService): int
    {
        if (! $this->hasTokenConfig($config)) {
            $this->error('Zoom credentials are not configured. Set account_id, client_id, and client_secret first.');

            return self::FAILURE;
        }

        try {
            $token = $tokenService->accessToken();

            $response = Http::timeout($config->timeout)
                ->withToken($token)
                ->acceptJson()
                ->get("{$config->apiBaseUrl}/users/me");

            if (! $response->successful()) {
                $this->error('Zoom users/me request failed with status '.$response->status().'.');
                $this->line($response->body());

                return self::FAILURE;
            }

            /** @var array<string, mixed> $data */
            $data = $response->json();
            $userId = trim((string) ($data['id'] ?? ''));

            if ($userId === '') {
                $this->error('Zoom users/me response did not include an id field.');
                $this->line($response->body());

                return self::FAILURE;
            }

            $this->info('Zoom users/me resolved successfully.');
            $this->line('User ID: '.$userId);
            $this->line('Email: '.((string) ($data['email'] ?? '(unknown)')));

            if ((bool) $this->option('json')) {
                $this->line('Response JSON:');
                $this->line((string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Zoom users/me lookup failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function hasTokenConfig(ZoomConfig $config): bool
    {
        return filled($config->accountId)
            && filled($config->clientId)
            && filled($config->clientSecret);
    }
}
