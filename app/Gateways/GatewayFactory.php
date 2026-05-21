<?php

namespace App\Gateways;

use InvalidArgumentException;

class GatewayFactory
{
    public static function make(string $channel): NotificationGateway
    {
        return match ($channel) {
            'sms' => app(SmsGateway::class),
            'email' => app(EmailGateway::class),
            default => throw new InvalidArgumentException("Unsupported channel: {$channel}"),
        };
    }
}