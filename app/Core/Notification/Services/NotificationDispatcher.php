<?php

namespace App\Core\Notification\Services;

use App\Core\Notification\DTO\ChannelResult;
use App\Core\Notification\DTO\NotificationMessage;
use Psr\Log\LoggerInterface;
use Throwable;

final class NotificationDispatcher
{
    public function __construct(
        private NotificationChannelRegistry $channels,
        private NotificationRegistry $notifications,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Dispatch a NotificationMessage to the requested channels. If `$toChannels` is empty
     * the dispatcher will consult the `NotificationRegistry` for supported channels for
     * the given message type/key.
     *
     * Returns an array of ChannelResult keyed by channel name.
     *
     * @param array<int,string> $toChannels
     * @return array<string, ChannelResult>
     */
    public function dispatch(NotificationMessage $message, array $toChannels = []): array
    {
        $channels = $toChannels;

        if (empty($channels)) {
            // look up in NotificationRegistry definitions
            $defs = $this->notifications->definitions();
            foreach ($defs as $def) {
                if (($def['key'] ?? '') === $message->type) {
                    $channels = $def['supported_channels'] ?? [];
                    break;
                }
            }
        }

        $results = [];

        foreach ($channels as $channelName) {
            if (! is_string($channelName) || $channelName === '') {
                continue;
            }

            if (! $this->channels->has($channelName)) {
                $this->logger->warning('NotificationDispatcher: unknown channel', ['channel' => $channelName, 'message' => $message->toArray()]);
                $results[$channelName] = ChannelResult::failure($channelName, null, ['message' => 'channel not registered']);
                continue;
            }

            try {
                $channel = $this->channels->resolve($channelName);

                $channelMessage = $channel->convert($message);

                $res = $channel->send($channelMessage);

                $results[$channelName] = $res;
            } catch (Throwable $e) {
                $this->logger->error('NotificationDispatcher: exception while sending', ['channel' => $channelName, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                $results[$channelName] = ChannelResult::failure($channelName, null, ['message' => $e->getMessage()]);
            }
        }

        return $results;
    }
}
