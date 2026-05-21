<?php

namespace App\Gateways;

use Illuminate\Support\Facades\Log;

class EmailGateway implements NotificationGateway
{
    public function send(string $recipient, string $message): bool
    {
        Log::info("Email sent to {$recipient}: {$message}");
        return true;
    }
    
    public function getChannelName(): string
    {
        return 'email';
    }
}