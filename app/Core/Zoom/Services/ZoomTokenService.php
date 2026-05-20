<?php

namespace App\Core\Zoom\Services;

use App\Core\Zoom\Data\ZoomConfig;
use App\Core\Zoom\Exceptions\ZoomSmsException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches and caches a Server-to-Server OAuth access token from Zoom.
 *
 * Token characteristics (per Zoom docs):
 *   - Expires in 3600 seconds.
 *   - No refresh token — must call /oauth/token again to get a new one.
 *   - Multiple tokens can be active simultaneously.
 *   - Tokens stop working when the app is deactivated.
 */
class ZoomTokenService
{
    private const CACHE_KEY = 'zoom.s2s.access_token';

    public function __construct(private readonly ZoomConfig $config) {}

    /**
     * Returns a valid Bearer access token, fetching a fresh one when needed.
     *
     * @throws ZoomSmsException
     */
    public function accessToken(): string
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->fetchAndCache();
    }

    /**
     * Bypasses the cache and forces a new token to be fetched.
     * Call this when a downstream request returns 401.
     *
     * @throws ZoomSmsException
     */
    public function forceRefresh(): string
    {
        Cache::forget(self::CACHE_KEY);

        return $this->fetchAndCache();
    }

    /**
     * @throws ZoomSmsException
     */
    private function fetchAndCache(): string
    {
        $credentials = base64_encode("{$this->config->clientId}:{$this->config->clientSecret}");

        try {
            $response = Http::timeout($this->config->timeout)
                ->withHeaders([
                    'Authorization' => "Basic {$credentials}",
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                ->asForm()
                ->post($this->config->tokenUrl, [
                    'grant_type' => 'account_credentials',
                    'account_id' => $this->config->accountId,
                ]);
        } catch (ConnectionException $exception) {
            throw ZoomSmsException::tokenRequestFailed($exception->getMessage());
        }

        if (! $response->successful()) {
            Log::error('Zoom S2S token request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ZoomSmsException::tokenRequestFailed(
                "HTTP {$response->status()}: {$response->body()}"
            );
        }

        /** @var array{access_token?: string} $data */
        $data = $response->json();
        $token = $data['access_token'] ?? '';

        if ($token === '') {
            throw ZoomSmsException::tokenRequestFailed('Response contained no access_token.');
        }

        Cache::put(self::CACHE_KEY, $token, $this->config->tokenCacheTtl);

        Log::debug('Zoom S2S access token refreshed and cached.', [
            'ttl_seconds' => $this->config->tokenCacheTtl,
        ]);

        return $token;
    }
}
