<?php

use App\Core\Notification\DTO\NotificationMessage;
use PHPUnit\Framework\TestCase;

final class NotificationMessageTest extends TestCase
{
    public function testConstructAndToArray(): void
    {
        $msg = new NotificationMessage(
            'alert',
            'Test title',
            'Test body',
            ['foo' => 'bar'],
            ['user:1'],
            'system',
            5,
            ['source' => 'unit-test']
        );

        $arr = $msg->toArray();

        $this->assertSame('alert', $arr['type']);
        $this->assertSame('Test title', $arr['title']);
        $this->assertContains('user:1', $arr['recipients']);
        $this->assertSame('unit-test', $arr['metadata']['source']);
    }

    public function testFromArrayAndWithAddedRecipient(): void
    {
        $payload = [
            'type' => 'notice',
            'recipients' => ['user:2'],
        ];

        $msg = NotificationMessage::fromArray($payload);

        $new = $msg->withAddedRecipient('user:3');

        $this->assertNotContains('user:3', $msg->recipients);
        $this->assertContains('user:3', $new->recipients);
    }
}
