<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class SetupRabbitMQQueues extends Command
{
    protected $signature = 'rabbitmq:setup-queues';
    protected $description = 'Declare queues high and low in RabbitMQ';

    public function handle()
    {
        $queues = ['high', 'low'];
        foreach ($queues as $queue) {
            Queue::connection('rabbitmq')->declareQueue($queue);
            $this->info("Queue '{$queue}' declared");
        }
    }
}