<?php

namespace App\Core\Cpanel\Jobs;

use App\Core\Cpanel\Services\CpanelMailboxManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PerformMailboxWriteOperation implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var array<string, mixed>
     */
    public array $payload;

    public int $tries;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $operation,
        array $payload = []
    ) {
        $this->payload = $payload;
        $this->tries = max((int) config('services.cpanel.queue_tries', 3), 1);
        $this->onQueue((string) config('services.cpanel.queue_name', 'default'));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        $backoff = (string) config('services.cpanel.queue_backoff', '10,30,60');

        return collect(explode(',', $backoff))
            ->map(fn (string $value): int => max((int) trim($value), 1))
            ->filter(fn (int $value): bool => $value > 0)
            ->values()
            ->all();
    }

    public function handle(CpanelMailboxManager $mailboxManager): void
    {
        $mailboxManager->executeWriteOperation(
            operation: $this->operation,
            payload: $this->payload,
            fromQueue: true,
        );
    }
}
