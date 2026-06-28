<?php

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
        ?DateTimeImmutable $scheduledAt = null
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
    }

    /**
     * Create from a plain array (useful when hydrating from storage or HTTP requests).
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
            $scheduled
        );
    }

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
        ];
    }

    /**
     * Mutates in-place by adding a recipient.
     */
    public function addRecipient(string $recipient): void
    {
        if (!in_array($recipient, $this->recipients, true)) {
            $this->recipients[] = $recipient;
        }
    }

    /**
     * Returns a copy with an added recipient (immutable-style helper).
     */
    public function withAddedRecipient(string $recipient): self
    {
        $clone = clone $this;
        $clone->addRecipient($recipient);

        return $clone;
    }

    /**
     * Set a metadata key.
     */
    public function setMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    /**
     * Returns a copy with merged metadata.
     */
    public function withMergedMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = array_merge($this->metadata, $metadata);

        return $clone;
    }
}
