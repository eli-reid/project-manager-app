<?php
/**
 * Interface for notification channels.
 *
 * Defines the contract for converting a generic notification message into a channel-specific message
 * and sending it through the respective channel.
 */

declare(strict_types=1);


namespace App\Core\Notification\Contracts;

use App\Core\Notification\DTO\ChannelMessage;
use App\Core\Notification\DTO\NotificationMessage;
use App\Core\Notification\DTO\ChannelResult;


interface NotificationChannel
{
    /**
     * Convert the generic NotificationMessage into a channel-specific message.
     *
     * @param NotificationMessage $message
     * @return ChannelMessage
     */
    public function convert(NotificationMessage $message): ChannelMessage;

    /**
     * Deliver the channel-specific message.
     *
     * @param ChannelMessage $message
     * @return ChannelResult
     */
    public function send(ChannelMessage $message): ChannelResult;
}
