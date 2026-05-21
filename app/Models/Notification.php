<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'recipient_id',
        'channel',
        'message',
        'priority',
        'status',
        'failure_reason',
        'retry_count',
        'idempotency_key',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_LOW = 'low';

    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_EMAIL = 'email';
}