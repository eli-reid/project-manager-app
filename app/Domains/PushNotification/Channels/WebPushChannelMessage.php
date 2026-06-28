<?php

namespace App\Domains\PushNotification\Channels;

use App\Core\Notification\DTO\ChannelMessage;

final class WebPushChannelMessage extends ChannelMessage
{
    public function channelName(): string
    {
        return 'webpush';
    }
}
