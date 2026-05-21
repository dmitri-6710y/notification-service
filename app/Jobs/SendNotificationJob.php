<?php

namespace App\Jobs;

use App\Gateways\GatewayFactory;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public Notification $notification;
    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function handle(): void
    {
        try {
            Log::info('Job started for notification: ' . $this->notification->uuid);
            
            if ($this->notification->status === Notification::STATUS_PENDING) {
                $this->notification->update(['status' => Notification::STATUS_QUEUED]);
            }
            
            Log::info('GatewayFactory::make for channel: ' . $this->notification->channel);
            $gateway = GatewayFactory::make($this->notification->channel);
            
            $this->notification->update(['status' => Notification::STATUS_SENT]);
            
            $success = $gateway->send(
                $this->notification->recipient_id,
                $this->notification->message
            );
            
            if ($success) {
                $this->notification->update(['status' => Notification::STATUS_DELIVERED]);
                Log::info("Notification {$this->notification->uuid} delivered");
            } else {
                throw new \Exception("Gateway returned false for {$this->notification->channel}");
            }
        } catch (\Throwable $e) {
            Log::error('Job failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'notification_id' => $this->notification->id
            ]);
            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $this->notification->update([
            'status' => Notification::STATUS_FAILED,
            'failure_reason' => substr($e->getMessage(), 0, 255),
        ]);
        Log::error("Notification {$this->notification->uuid} failed: " . $e->getMessage());
    }

}