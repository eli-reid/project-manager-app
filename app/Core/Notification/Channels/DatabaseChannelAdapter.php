<?php

namespace App\Core\Notification\Channels;

use App\Core\Notification\Contracts\NotificationChannel;
use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\ChannelResult;
use App\Core\Notification\DTO\NotificationMessage;
use Illuminate\Support\Facades\DB;

final class DatabaseChannelAdapter implements NotificationChannel
{
    public function convert(NotificationMessage $message): ChannelMessage
    {
        return new class(['id' => $message->id, 'type' => $message->type, 'title' => $message->title, 'body' => $message->body, 'data' => $message->data, 'metadata' => $message->metadata]) extends ChannelMessage
        {
            public function channelName(): string
            {
                return 'database';
            }
        };
    }

    public function send(ChannelMessage $message): ChannelResult
    {
        $payload = $message->toArray();
        $metadata = $payload['metadata'] ?? [];

        $notifiableType = $metadata['notifiable_type'] ?? null;
        $notifiableId = $metadata['notifiable_id'] ?? null;

        if (! is_string($notifiableType) || $notifiableType === '' || ! is_string($notifiableId) || $notifiableId === '') {
            return ChannelResult::failure('database', null, ['message' => 'notifiable context missing']);
        }

        DB::table('notifications')->insert([
            'id' => (string) str()->uuid(),
            'type' => (string) ($payload['type'] ?? 'app.notification'),
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiableId,
            'data' => json_encode([
                'title' => $payload['title'] ?? null,
                'body' => $payload['body'] ?? null,
                'payload' => $payload['data'] ?? [],
                'key' => $payload['type'] ?? null,
            ], JSON_THROW_ON_ERROR),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ChannelResult::success('database', null, ['message' => 'stored']);
    }
}
