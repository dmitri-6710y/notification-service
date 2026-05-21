<?php

namespace App\Gateways;

interface NotificationGateway
{
    public function send(string $recipient, string $message): bool;
    
    public function getChannelName(): string;
}