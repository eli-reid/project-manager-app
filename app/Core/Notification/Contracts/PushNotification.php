<?php

namespace App\Core\Notification\Contracts;

interface PushNotification
{
    public function toPush(): PushMessage;
}
