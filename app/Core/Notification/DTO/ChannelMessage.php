<?php
declare(strict_types=1);

namespace App\Core\Notification\DTO;

/**
 * Base channel-specific message. Channels should extend this class and add
 * channel-specific fields (e.g. subject, to, from, headers).
 */
abstract class ChannelMessage
{
	/**
	 * Generic payload for the channel implementation.
	 * @var array<string,mixed>
	 */
	public array $payload;

	public function __construct(array $payload = [])
	{
		$this->payload = $payload;
	}

	/**
	 * Convert channel message to array for serialization.
	 * @return array<string,mixed>
	 */
	public function toArray(): array
	{
		return $this->payload;
	}

	/**
	 * Channel name (e.g. "sms", "email", "push").
	 */
	abstract public function channelName(): string;
}