<?php

namespace App\Domains\Email\Channels;

use App\Core\Notification\Contracts\NotificationChannel;
use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\ChannelResult;
use App\Core\Notification\DTO\NotificationMessage;
use Illuminate\Support\Facades\Mail;

final class MailChannel implements NotificationChannel
{
    public function convert(NotificationMessage $message): ChannelMessage
    {
        return new class(['id' => $message->id, 'title' => $message->title ?? 'Notification', 'body' => $message->body ?? '', 'data' => $message->data, 'recipients' => $message->recipients, 'metadata' => $message->metadata]) extends ChannelMessage
        {
            public function channelName(): string
            {
                return 'mail';
            }
        };
    }

    public function send(ChannelMessage $message): ChannelResult
    {
        $payload = $message->toArray();
        $recipients = collect($payload['recipients'] ?? [])
            ->filter(fn (mixed $recipient): bool => is_string($recipient) && str_starts_with($recipient, 'email:'))
            ->map(fn (string $recipient): string => substr($recipient, 6))
            ->filter(fn (string $email): bool => $email !== '')
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return ChannelResult::failure('mail', null, ['message' => 'no email recipients']);
        }

        $subject = (string) ($payload['title'] ?? 'Notification');
        $body = (string) ($payload['body'] ?? '');

        foreach ($recipients as $email) {
            Mail::raw($body, function ($mail) use ($email, $subject): void {
                $mail->to($email)->subject($subject);
            });
        }

        return ChannelResult::success('mail', null, [
            'message' => 'sent',
            'recipientStatus' => $recipients->mapWithKeys(fn (string $email): array => [$email => ['status' => 'delivered', 'metadata' => []]])->all(),
        ]);
    }
}
