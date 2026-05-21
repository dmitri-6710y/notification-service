<?php

namespace App\Gateways;

use Illuminate\Support\Facades\Log;

class SmsGateway implements NotificationGateway
{
    public function send(string $recipient, string $message): bool
    {
        Log::info("SMS sent to {$recipient}: {$message}");
        
        return true;
    }
    
    public function getChannelName(): string
    {
        return 'sms';
    }
}