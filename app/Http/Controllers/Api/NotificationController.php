<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendNotificationRequest;
use App\Services\IdempotencyService;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function send(SendNotificationRequest $request, IdempotencyService $idempotency): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        
        if ($idempotencyKey) {
            $existingNotifications = $idempotency->getExistingNotifications($idempotencyKey);
            if ($existingNotifications->isNotEmpty()) {
                return response()->json([
                    'message' => 'Already processed (idempotent)',
                    'notifications' => $existingNotifications->map(fn($n) => [
                        'id' => $n->id,
                        'uuid' => $n->uuid,
                        'recipient_id' => $n->recipient_id,
                        'status' => $n->status,
                    ]),
                ], 200);
            }
        }
        
        $createdNotifications = collect();
        
        DB::transaction(function () use ($request, $idempotencyKey, &$createdNotifications) {
            foreach ($request->recipients as $recipient) {
                $uuid = (string) Str::uuid();
                
                $notification = Notification::create([
                    'uuid' => $uuid,
                    'recipient_id' => $recipient,
                    'channel' => $request->channel,
                    'message' => $request->message,
                    'priority' => $request->priority,
                    'status' => Notification::STATUS_PENDING,
                    'retry_count' => 0,
                    'idempotency_key' => $idempotencyKey,
                ]);
                
                $createdNotifications->push($notification);
            }
        });
        
        foreach ($createdNotifications as $notification) {
            $queue = $notification->priority === 'high' ? 'high' : 'low';
            SendNotificationJob::dispatch($notification)->onQueue($queue);
        }
        
        if ($idempotencyKey && $createdNotifications->isNotEmpty()) {
            $idempotency->storeNotifications($idempotencyKey, $createdNotifications);
        }
        
        return response()->json([
            'message' => 'Notifications queued',
            'notifications' => $createdNotifications->map(fn($n) => [
                'id' => $n->id,
                'uuid' => $n->uuid,
                'recipient_id' => $n->recipient_id,
                'status' => $n->status,
            ]),
        ], 201);
    }
    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|string|max:255',
        ]);

        $recipientId = $request->query('recipient_id');

        $notifications = Notification::where('recipient_id', $recipientId)
            ->orderBy('created_at', 'desc')
            ->get([
                'uuid',
                'recipient_id',
                'channel',
                'message',
                'priority',
                'status',
                'failure_reason',
                'retry_count',
                'created_at',
            ]);

        return response()->json([
            'recipient_id' => $recipientId,
            'notifications' => $notifications->map(fn($n) => [
                'uuid' => $n->uuid,
                'channel' => $n->channel,
                'message' => $n->message,
                'priority' => $n->priority,
                'status' => $n->status,
                'failure_reason' => $n->failure_reason,
                'retry_count' => $n->retry_count,
                'created_at' => $n->created_at->toDateTimeString(),
            ]),
            'total' => $notifications->count(),
        ]);
    }

}