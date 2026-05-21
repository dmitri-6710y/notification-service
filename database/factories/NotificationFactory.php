<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition()
    {
        return [
            'uuid' => Str::uuid(),
            'recipient_id' => $this->faker->phoneNumber,
            'channel' => $this->faker->randomElement(['sms', 'email']),
            'message' => $this->faker->sentence,
            'priority' => $this->faker->randomElement(['high', 'low']),
            'status' => 'pending',
            'retry_count' => 0,
            'failure_reason' => null,
        ];
    }
}