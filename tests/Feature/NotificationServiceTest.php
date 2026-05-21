<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;
use App\Gateways\SmsGateway;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
        Queue::fake();
    }

    #[Test]
    public function it_can_send_notifications_to_multiple_recipients()
    {
        $payload = [
            'message' => 'Hello multiple',
            'recipients' => ['+111', '+222', 'test@example.com'],
            'channel' => 'sms',
            'priority' => 'low'
        ];

        $response = $this->postJson('/api/notifications/send', $payload);
        $response->assertStatus(201);
        
        $this->assertDatabaseCount('notifications', 3);
        Queue::assertPushed(SendNotificationJob::class, 3);
    }

    #[Test]
    public function it_respects_idempotency_key()
    {
        $payload = [
            'message' => 'Idempotent message',
            'recipients' => ['+999'],
            'channel' => 'email',
            'priority' => 'high'
        ];
        $headers = ['Idempotency-Key' => 'unique-key-123'];

        $response1 = $this->postJson('/api/notifications/send', $payload, $headers);
        $response1->assertStatus(201);
        $firstUuid = $response1->json('notifications.0.uuid');

        $response2 = $this->postJson('/api/notifications/send', $payload, $headers);
        $response2->assertStatus(200);
        $secondUuid = $response2->json('notifications.0.uuid');

        $this->assertEquals($firstUuid, $secondUuid);
        $this->assertDatabaseCount('notifications', 1);
        Queue::assertPushed(SendNotificationJob::class, 1);
    }

    #[Test]
    public function it_returns_status_for_recipient()
    {
        $notification1 = Notification::factory()->create([
            'status' => 'delivered',
        ]);
        $recipient = $notification1->recipient_id;

        Notification::factory()->create([
            'recipient_id' => $recipient,
            'status' => 'failed',
            'failure_reason' => 'Invalid phone',
        ]);

        $this->assertDatabaseCount('notifications', 2);

        $response = $this->getJson("/api/notifications/status?recipient_id={$recipient}");
        $response->assertStatus(200)
            ->assertJsonCount(2, 'notifications')
            ->assertJsonPath('recipient_id', $recipient)
            ->assertJsonPath('notifications.0.status', 'delivered')
            ->assertJsonPath('notifications.1.status', 'failed');
    }
    #[Test]
    public function it_dispatches_high_priority_jobs_to_high_queue()
    {
        $payload = [
            'message' => 'High priority',
            'recipients' => ['high@example.com'],
            'channel' => 'email',
            'priority' => 'high'
        ];
        
        $this->postJson('/api/notifications/send', $payload);
        
        Queue::assertPushed(SendNotificationJob::class, function ($job) {
            return $job->queue === 'high';
        });
    }

    #[Test]
    public function it_dispatches_low_priority_jobs_to_low_queue()
    {
        $payload = [
            'message' => 'Low priority',
            'recipients' => ['low@example.com'],
            'channel' => 'email',
            'priority' => 'low'
        ];
        
        $this->postJson('/api/notifications/send', $payload);
        
        Queue::assertPushed(SendNotificationJob::class, function ($job) {
            return $job->queue === 'low';
        });
    }

    #[Test]
    public function job_updates_status_on_successful_send()
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('send')->once()->andReturn(true);
        });

        $notification = Notification::factory()->create([
            'channel' => 'sms',
            'status' => 'pending',
        ]);

        $job = new SendNotificationJob($notification);
        $job->handle();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'delivered'
        ]);
    }

    #[Test]
    public function job_handles_failure_and_sets_failed_status()
    {
        $this->mock(SmsGateway::class, function ($mock) {
            $mock->shouldReceive('send')->once()->andThrow(new \Exception('Gateway timeout'));
        });

        $notification = Notification::factory()->create([
            'channel' => 'sms',
            'status' => 'pending',
        ]);

        $job = new SendNotificationJob($notification);
        
        try {
            $job->handle();
        } catch (\Exception $e) {
            $job->failed($e);
        }

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'failed',
        ]);
    }
}