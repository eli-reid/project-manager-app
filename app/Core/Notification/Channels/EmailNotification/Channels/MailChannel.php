<?php
/**
 * Mail channel implementation for the notification system.
 *
 * Converts notification messages into channel messages suitable for email
 * delivery and sends them using Laravel's mail facilities.
 */

declare(strict_types=1);

namespace App\Core\Notification\Channels\EmailNotification\Channels;

use App\Core\Notification\Contracts\NotificationChannel;
use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\ChannelResult;
use App\Core\Notification\DTO\NotificationMessage;
use Illuminate\Support\Facades\Mail;

/**
 * Implementation of the mail channel for sending notifications via email.
 * 
 * This channel filters recipients for email addresses, formats the message, and sends it using Laravel's mail system.
 * @final
 * @implements NotificationChannel
 * 
 */

final class MailChannel implements NotificationChannel
{
    /**
     * Converts a notification message into a channel message suitable for the mail channel.
     *
     * @param NotificationMessage $message
     * @return ChannelMessage
     */
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

    /**
     * Sends a channel message via the mail channel.
     *
     * @param ChannelMessage $message
     * @return ChannelResult
     */

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
