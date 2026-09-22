<?php

namespace Modules\ActivityLog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ActivityLog\Models\SmsLog;

/**
 * @extends Factory<SmsLog>
 */
class SmsLogFactory extends Factory
{
    protected $model = SmsLog::class;

    /**
     * Define the model's default state. `response` is a JSON column with no
     * cast on the model, so it holds the gateway response already encoded —
     * as CreateSmsLogAction stores it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => $this->faker->e164PhoneNumber(),
            'message' => $this->faker->sentence(),
            'status' => 'success',
            'response' => json_encode(['message_id' => $this->faker->uuid()], JSON_THROW_ON_ERROR),
        ];
    }

    /**
     * Indicate that the gateway refused the message.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'response' => 'false',
        ]);
    }
}
