<?php

namespace App\Domains\PushNotification\Commands;

use Illuminate\Console\Command;

class SetupVapidKeysCommand extends Command
{
    protected $signature = 'notifications:vapid:setup
        {--public-key= : VAPID public key (optional)}
        {--private-key= : VAPID private key (optional)}
        {--subject= : VAPID subject URL or mailto address}
        {--env-file=.env : Environment file path to update}
        {--force : Overwrite existing VAPID keys}
        {--show : Display resulting VAPID keys in output}';

    protected $description = 'Generate or set VAPID keys and persist them to an env file for web push notifications';

    public function handle(): int
    {
        $envFile = (string) $this->option('env-file');

        if (! file_exists($envFile)) {
            $this->error('Env file not found: '.$envFile);

            return self::FAILURE;
        }

        $publicKeyOption = trim((string) $this->option('public-key'));
        $privateKeyOption = trim((string) $this->option('private-key'));
        $subjectOption = trim((string) $this->option('subject'));

        if (($publicKeyOption === '') xor ($privateKeyOption === '')) {
            $this->error('Provide both --public-key and --private-key together.');

            return self::FAILURE;
        }

        $contents = (string) file_get_contents($envFile);
        if ($contents === '') {
            $this->error('Unable to read env file: '.$envFile);

            return self::FAILURE;
        }

        $existingPublic = $this->envValue($contents, 'VAPID_PUBLIC_KEY');
        $existingPrivate = $this->envValue($contents, 'VAPID_PRIVATE_KEY');

        $hasExistingKeys = $existingPublic !== null && $existingPublic !== ''
            && $existingPrivate !== null && $existingPrivate !== '';

        if ($hasExistingKeys && ! (bool) $this->option('force')) {
            $this->error('VAPID keys already exist. Use --force to replace them.');

            return self::FAILURE;
        }

        $publicKey = $publicKeyOption;
        $privateKey = $privateKeyOption;

        if ($publicKey === '' && $privateKey === '') {
            if ($envFile !== '.env') {
                $this->error('Automatic VAPID generation only supports the default .env file. For custom env files, provide --public-key and --private-key.');

                return self::FAILURE;
            }

            $this->line('Generating VAPID keys using webpush:vapid...');

            $status = $this->call('webpush:vapid', ['--no-interaction' => true]);

            if ($status !== self::SUCCESS) {
                $this->error('Unable to auto-generate VAPID keys in this environment. Provide --public-key and --private-key manually.');

                return self::FAILURE;
            }

            $contents = (string) file_get_contents($envFile);
            $publicKey = (string) $this->envValue($contents, 'VAPID_PUBLIC_KEY');
            $privateKey = (string) $this->envValue($contents, 'VAPID_PRIVATE_KEY');

            if ($publicKey === '' || $privateKey === '') {
                $this->error('webpush:vapid ran, but keys were not found in .env. Provide --public-key and --private-key manually.');

                return self::FAILURE;
            }
        }

        $subject = $subjectOption !== ''
            ? $subjectOption
            : ((string) ($this->envValue($contents, 'VAPID_SUBJECT') ?? $this->defaultSubject()));

        $updated = $this->setEnvValue($contents, 'VAPID_PUBLIC_KEY', $publicKey);
        $updated = $this->setEnvValue($updated, 'VAPID_PRIVATE_KEY', $privateKey);
        $updated = $this->setEnvValue($updated, 'VAPID_SUBJECT', $subject);

        file_put_contents($envFile, $updated);

        $this->info('VAPID keys configured successfully.');
        $this->line('Env file: '.$envFile);
        $this->line('VAPID_SUBJECT='.$subject);

        if ((bool) $this->option('show')) {
            $this->line('VAPID_PUBLIC_KEY='.$publicKey);
            $this->line('VAPID_PRIVATE_KEY='.$privateKey);
        }

        return self::SUCCESS;
    }

    private function envValue(string $contents, string $key): ?string
    {
        $matches = [];
        if (preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches) !== 1) {
            return null;
        }

        return trim((string) ($matches[1] ?? ''), " \t\n\r\0\x0B\"'");
    }

    private function setEnvValue(string $contents, string $key, string $value): string
    {
        $line = $key.'='.$value;

        if (preg_match('/^'.preg_quote($key, '/').'=.*$/m', $contents) === 1) {
            $updated = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $line, $contents, 1);

            return (string) $updated;
        }

        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";

        return rtrim($contents).$lineEnding.$line.$lineEnding;
    }

    private function defaultSubject(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return 'mailto:admin@'.$host;
        }

        return 'mailto:admin@example.com';
    }
}
