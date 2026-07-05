<?php

declare(strict_types=1);
namespace App\Core\Notification\DTO;

use DateTimeImmutable;

/**
 * Generic, transport-agnostic notification message used by channels and plugins.
 */
final class NotificationMessage
{
    public string $id;

    public string $type;

    public ?string $title;

    public ?string $body;

    public array $data;

    /**
     * A list of recipients expressed as strings (email, user:id, phone, etc.).
     */
    public array $recipients;

    public ?string $sender;

    public int $priority;

    public array $metadata;

    public DateTimeImmutable $createdAt;

    public ?DateTimeImmutable $scheduledAt;

    /**
     * The time at which the message was delivered (if applicable).
     */
    public ?DateTimeImmutable $deliveredAt;

    public function __construct(
        string $type,
        ?string $title = null,
        ?string $body = null,
        array $data = [],
        array $recipients = [],
        ?string $sender = null,
        int $priority = 0,
        array $metadata = [],
        ?string $id = null,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $scheduledAt = null,
        ?DateTimeImmutable $deliveredAt = null
    ) {
        $this->id = $id ?? uniqid('', true);
        $this->type = $type;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->recipients = array_values($recipients);
        $this->sender = $sender;
        $this->priority = $priority;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
        $this->scheduledAt = $scheduledAt;
        $this->deliveredAt = $deliveredAt;
    }

    /**
     * Create from a plain array (useful when hydrating from storage or HTTP requests).
     *
     * @param array<string,mixed> $payload
     * @return self
     */
    public static function fromArray(array $payload): self
    {
        $created = isset($payload['createdAt']) && $payload['createdAt'] instanceof DateTimeImmutable
            ? $payload['createdAt']
            : (isset($payload['createdAt']) ? new DateTimeImmutable((string) $payload['createdAt']) : null);

        $scheduled = isset($payload['scheduledAt']) && $payload['scheduledAt'] instanceof DateTimeImmutable
            ? $payload['scheduledAt']
            : (isset($payload['scheduledAt']) ? new DateTimeImmutable((string) $payload['scheduledAt']) : null);

        return new self(
            (string) ($payload['type'] ?? 'generic'),
            $payload['title'] ?? null,
            $payload['body'] ?? null,
            $payload['data'] ?? [],
            $payload['recipients'] ?? [],
            $payload['sender'] ?? null,
            isset($payload['priority']) ? (int) $payload['priority'] : 0,
            $payload['metadata'] ?? [],
            $payload['id'] ?? null,
            $created,
            $scheduled,
            isset($payload['deliveredAt']) && $payload['deliveredAt'] instanceof DateTimeImmutable
                ? $payload['deliveredAt']
                : (isset($payload['deliveredAt']) ? new DateTimeImmutable((string) $payload['deliveredAt']) : null)
        );
    }

    /**
     * Convert the notification message to an array for serialization.
     *      *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'recipients' => $this->recipients,
            'sender' => $this->sender,
            'priority' => $this->priority,
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt,
            'scheduledAt' => $this->scheduledAt,
            'deliveredAt' => $this->deliveredAt,
        ];
    }

    /**
     * Mutates in-place by adding a recipient.
     * @param string $recipient
     * @return void
     */
    public function addRecipient(string $recipient): void
    {
        if (!in_array($recipient, $this->recipients, true)) {
            $this->recipients[] = $recipient;
        }
    }

    /**
     * Returns a copy with an added recipient (immutable-style helper).
     *
     * @param string $recipient
     * @return self
     */
    public function withAddedRecipient(string $recipient): self
    {
        $clone = clone $this;
        $clone->addRecipient($recipient);

        return $clone;
    }

    /**
     * Set a metadata key (mutates in-place).
     *
     * @param string $key
     * @param mixed $value
     */
    public function setMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    /**
     * Returns a copy with merged metadata (immutable-style helper).
     *
     * @param array<string,mixed> $metadata
     * @return self
     */
    public function withMergedMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = array_merge($this->metadata, $metadata);

        return $clone;
    }
}
