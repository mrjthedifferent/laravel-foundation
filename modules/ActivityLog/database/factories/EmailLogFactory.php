<?php

namespace Modules\ActivityLog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ActivityLog\Models\EmailLog;

/**
 * @extends Factory<EmailLog>
 */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => str()->uuid()->toString(),
            'to_email' => $this->faker->safeEmail(),
            'to_name' => $this->faker->name(),
            'cc' => [],
            'bcc' => [],
            'from_email' => $this->faker->safeEmail(),
            'from_name' => $this->faker->company(),
            'subject' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'mailer' => 'smtp',
            'notification' => null,
            'status' => 'pending',
            'error' => null,
            'metadata' => [],
            'sent_at' => null,
        ];
    }

    /**
     * Indicate that the email was sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Indicate that sending the email failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error' => $this->faker->sentence(),
        ]);
    }
}
