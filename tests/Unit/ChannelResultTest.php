<?php

use App\Core\Notification\DTO\ChannelResult;
use PHPUnit\Framework\TestCase;

final class ChannelResultTest extends TestCase
{
    public function testSuccessFactoryAndArray(): void
    {
        $res = ChannelResult::success('sms', 'ext-123', [
            'statusCode' => 200,
            'message' => 'OK',
            'rawResponse' => ['id' => 'ext-123'],
            'recipientStatus' => ['+100' => ['status' => 'delivered', 'metadata' => []]],
        ]);

        $this->assertTrue($res->success);
        $this->assertSame('sms', $res->channel);
        $this->assertSame('ext-123', $res->externalId);
        $this->assertTrue($res->wasDeliveredTo('+100'));

        $arr = $res->toArray();
        $this->assertArrayHasKey('success', $arr);
        $this->assertSame(200, $arr['statusCode']);
    }

    public function testFromArrayFailure(): void
    {
        $payload = [
            'success' => false,
            'channel' => 'email',
            'message' => 'Rejected',
        ];

        $res = ChannelResult::fromArray($payload);

        $this->assertFalse($res->success);
        $this->assertSame('email', $res->channel);
        $this->assertSame('Rejected', $res->message);
    }
}
