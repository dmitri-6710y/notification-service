<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

class IdempotencyService
{
    private const TTL_SECONDS = 86400; // 24 hours

    public function getExistingNotifications(string $key): Collection
    {
        $notificationIds = Redis::lrange($this->redisListKey($key), 0, -1);
        if (!empty($notificationIds)) {
            return Notification::whereIn('id', $notificationIds)->get();
        }
        
        return Notification::where('idempotency_key', $key)->get();
    }

    public function storeNotifications(string $key, $notifications): void
    {
        $ids = $notifications->pluck('id')->toArray();
        Redis::rpush($this->redisListKey($key), ...$ids);
        Redis::expire($this->redisListKey($key), self::TTL_SECONDS);
    }

    private function redisListKey(string $key): string
    {
        return "idempotency:{$key}:notifications";
    }
}