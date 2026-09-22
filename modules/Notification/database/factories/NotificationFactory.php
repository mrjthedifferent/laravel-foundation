<?php

namespace Modules\Notification\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Models\Notification;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'notifiable_type' => User::class,
            'notifiable_id' => User::factory(),
            'type' => NotificationType::Info->value,
            'data' => [
                'title' => $this->faker->sentence(),
                'body' => $this->faker->paragraph(),
                'data' => [],
            ],
            'read_at' => null,
        ];
    }

    /**
     * Mark the notification as read
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Mark the notification as unread
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Set the notification type
     */
    public function ofType(NotificationType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type->value,
        ]);
    }
}
