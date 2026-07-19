<?php

namespace App\PlugIns\Zoom\Channels;

use App\Core\Notification\Contracts\NotificationChannel;
use App\Core\Notification\Contracts\SmsMessage;
use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\ChannelResult;
use App\Core\Notification\DTO\NotificationMessage;
use App\PlugIns\Zoom\Exceptions\ZoomSmsException;
use App\PlugIns\Zoom\Services\ZoomSmsService;

final class ZoomSms implements NotificationChannel
{
    public function __construct(
        private readonly ZoomSmsService $smsService,
    ) {}

    public function convert(NotificationMessage $message): ChannelMessage
    {
        return new class(['id' => $message->id, 'title' => $message->title, 'body' => $message->body ?? '', 'data' => $message->data, 'recipients' => $message->recipients, 'sender' => $message->sender, 'metadata' => $message->metadata]) extends ChannelMessage
        {
            public function channelName(): string
            {
                return 'sms';
            }
        };
    }

    public function send(ChannelMessage $message): ChannelResult
    {
        if (! $this->smsService->isConfigured()) {
            return ChannelResult::failure('sms', null, ['message' => 'sms service not configured']);
        }

        $payload = $message->toArray();
        $body = trim((string) ($payload['body'] ?? ''));

        if ($body === '') {
            return ChannelResult::failure('sms', null, ['message' => 'sms body is empty']);
        }

        $recipients = collect($payload['recipients'] ?? [])
            ->filter(fn (mixed $recipient): bool => is_string($recipient) && $recipient !== '')
            ->map(function (string $recipient): string {
                if (str_starts_with($recipient, 'phone:')) {
                    return substr($recipient, 6);
                }

                return $recipient;
            })
            ->filter(fn (string $recipient): bool => $recipient !== '')
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return ChannelResult::failure('sms', null, ['message' => 'no sms recipients']);
        }

        $recipientStatus = [];
        $rawResponse = [];
        $externalId = null;
        $failedRecipient = null;
        $failureMessage = null;
        $failureStatusCode = null;

        foreach ($recipients as $recipient) {
            try {
                $result = $this->smsService->send($recipient, new class($recipient, $body, $payload) implements SmsMessage
                {
                    public function __construct(
                        private readonly string $to,
                        private readonly string $body,
                        private readonly array $payload,
                    ) {}

                    public function to(): string
                    {
                        return $this->to;
                    }

                    public function body(): string
                    {
                        return $this->body;
                    }

                    public function title(): ?string
                    {
                        $title = $this->payload['title'] ?? null;

                        return is_string($title) && $title !== '' ? $title : null;
                    }

                    public function from(): ?string
                    {
                        $sender = $this->payload['sender'] ?? null;

                        return is_string($sender) && $sender !== '' ? $sender : null;
                    }
                });

                if ($result === []) {
                    $recipientStatus[$recipient] = [
                        'status' => 'withheld',
                        'metadata' => ['reason' => 'consent_flow'],
                    ];

                    continue;
                }

                $recipientStatus[$recipient] = [
                    'status' => 'delivered',
                    'metadata' => [
                        'message_id' => $result['message_id'] ?? null,
                        'session_id' => $result['session_id'] ?? null,
                    ],
                ];

                $rawResponse[$recipient] = $result;
                $externalId ??= $result['message_id'] ?? null;
            } catch (ZoomSmsException $exception) {
                $failedRecipient = $recipient;
                $failureMessage = $exception->getMessage();
                $failureStatusCode = $exception->getCode() > 0
                    ? $exception->getCode()
                    : $this->extractHttpStatusCode($exception);
                $recipientStatus[$recipient] = [
                    'status' => 'failed',
                    'metadata' => ['exception' => $exception::class],
                ];

                break;
            }
        }

        if ($failedRecipient !== null) {
            return ChannelResult::failure('sms', $externalId, [
                'message' => $failureMessage,
                'statusCode' => $failureStatusCode,
                'recipientStatus' => $recipientStatus,
                'rawResponse' => $rawResponse,
                'metadata' => ['failed_recipient' => $failedRecipient],
            ]);
        }

        $successMessage = collect($recipientStatus)->contains(fn (array $status): bool => $status['status'] === 'delivered')
            ? 'sent'
            : 'withheld';

        return ChannelResult::success('sms', $externalId, [
            'message' => $successMessage,
            'recipientStatus' => $recipientStatus,
            'rawResponse' => $rawResponse,
        ]);
    }

    private function extractHttpStatusCode(ZoomSmsException $exception): ?int
    {
        if (! preg_match('/HTTP\s+(\d{3})/', $exception->getMessage(), $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
