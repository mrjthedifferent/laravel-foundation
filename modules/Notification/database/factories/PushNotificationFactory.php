<?php

namespace Modules\Notification\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Models\PushNotification;

class PushNotificationFactory extends Factory
{
    protected $model = PushNotification::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'recipient_type' => 'specific',
            'user_id' => User::factory(),
            'url' => null,
            'description' => null,
            'image' => null,
            'result' => null,
        ];
    }

    public function toSpecificUser(string $userId): static
    {
        return $this->state([
            'recipient_type' => 'specific',
            'user_id' => $userId,
        ]);
    }
}
