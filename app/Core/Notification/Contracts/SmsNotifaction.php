<?php

namespace App\Core\Notification\Contracts;

interface SmsServiceContract
{
    public function toSms(): SmsMessage;

}
