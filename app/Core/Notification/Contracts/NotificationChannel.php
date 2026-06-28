<?php

namespace App\Core\Notification\Contracts;

use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\NotificationMessage;


interface NotificationChannel
{
    /**
     * Convert the generic NotificationMessage into a channel-specific message.
     */
    public function convert(NotificationMessage $message): ChannelMessage;

    /**
     * Deliver the channel-specific message.
     */
    public function send(ChannelMessage $message): ChannelResult;
}
