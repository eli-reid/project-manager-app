<?php
    
namespace App\Core\Notification\Contracts;

interface SmsMessage
{
    public function to(): string;

    public function body(): string;

    public function from(): ?string;
}