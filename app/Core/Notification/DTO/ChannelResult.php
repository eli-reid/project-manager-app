<?php

declare(strict_types=1);

namespace App\Core\Notification\DTO;

use DateTimeImmutable;

/**
 * Result of delivering a ChannelMessage.
 */
final class ChannelResult
{
    public bool $success;

    public string $channel;

    public ?string $externalId;

    public ?int $statusCode;

    public ?string $message;

    /** @var array<string,mixed> */
    public array $rawResponse;

    /** @var array<string,array{status:string,metadata:array}> */
    public array $recipientStatus;

    public DateTimeImmutable $sentAt;

    public array $metadata;

    /**
     * @param array<string,mixed> $rawResponse
     * @param array<string,array{status:string,metadata:array}> $recipientStatus
     * @param array<string,mixed> $metadata
     */
    public function __construct(
        bool $success,
        string $channel,
        ?string $externalId = null,
        ?int $statusCode = null,
        ?string $message = null,
        array $rawResponse = [],
        array $recipientStatus = [],
        ?DateTimeImmutable $sentAt = null,
        array $metadata = []
    ) {
        $this->success = $success;
        $this->channel = $channel;
        $this->externalId = $externalId;
        $this->statusCode = $statusCode;
        $this->message = $message;
        $this->rawResponse = $rawResponse;
        $this->recipientStatus = $recipientStatus;
        $this->sentAt = $sentAt ?? new DateTimeImmutable();
        $this->metadata = $metadata;
    }

    /**
     * Create a successful ChannelResult instance.
     *
     * @param string $channel
     * @param string|null $externalId
     * @param array<string,mixed> $opts
     * @return self
     */

    public static function success(string $channel, ?string $externalId = null, array $opts = []): self
    {
        return new self(
            true,
            $channel,
            $externalId,
            $opts['statusCode'] ?? null,
            $opts['message'] ?? null,
            $opts['rawResponse'] ?? [],
            $opts['recipientStatus'] ?? [],
            $opts['sentAt'] ?? null,
            $opts['metadata'] ?? []
        );
    }

    /**
     * Create a failed ChannelResult instance.
     *
     * @param string $channel
     * @param string|null $externalId
     * @param array<string,mixed> $opts
     * @return self
     */
    public static function failure(string $channel, ?string $externalId = null, array $opts = []): self
    {
        return new self(
            false,
            $channel,
            $externalId,
            $opts['statusCode'] ?? null,
            $opts['message'] ?? null,
            $opts['rawResponse'] ?? [],
            $opts['recipientStatus'] ?? [],
            $opts['sentAt'] ?? null,
            $opts['metadata'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'channel' => $this->channel,
            'externalId' => $this->externalId,
            'statusCode' => $this->statusCode,
            'message' => $this->message,
            'rawResponse' => $this->rawResponse,
            'recipientStatus' => $this->recipientStatus,
            'sentAt' => $this->sentAt,
            'metadata' => $this->metadata,
        ];
    }
    /**
     * Create a ChannelResult instance from an array payload.
     *
     * @param array<string,mixed> $payload
     * @return self
     */

    public static function fromArray(array $payload): self
    {
        $sentAt = isset($payload['sentAt']) && $payload['sentAt'] instanceof DateTimeImmutable
            ? $payload['sentAt']
            : (isset($payload['sentAt']) ? new DateTimeImmutable((string) $payload['sentAt']) : null);

        return new self(
            (bool) ($payload['success'] ?? false),
            (string) ($payload['channel'] ?? 'unknown'),
            $payload['externalId'] ?? null,
            isset($payload['statusCode']) ? (int) $payload['statusCode'] : null,
            $payload['message'] ?? null,
            $payload['rawResponse'] ?? [],
            $payload['recipientStatus'] ?? [],
            $sentAt,
            $payload['metadata'] ?? []
        );
    }

    /**
     * Check if the message was delivered to the given recipient.
     *
     * @param string $recipient
     * @return bool
     */
    public function wasDeliveredTo(string $recipient): bool
    {
        if (!isset($this->recipientStatus[$recipient])) {
            return $this->success;
        }

        return $this->recipientStatus[$recipient]['status'] === 'delivered';
    }
}
