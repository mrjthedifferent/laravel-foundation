<?php

namespace Mrj\Foundation\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Mrj\Foundation\Models\Audit;

/**
 * An audit record of a user being updated. Pass `auditable_type` /
 * `auditable_id` (or use forAuditable()) to point it at another model.
 *
 * @extends Factory<Audit>
 */
class AuditFactory extends Factory
{
    protected $model = Audit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_type' => User::class,
            'user_id' => User::factory(),
            'event' => 'updated',
            'auditable_type' => User::class,
            'auditable_id' => User::factory(),
            'old_values' => ['name' => $this->faker->name()],
            'new_values' => ['name' => $this->faker->name()],
            'url' => $this->faker->url(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'tags' => null,
        ];
    }

    /**
     * An audit written by the system (console, queue) rather than a user.
     */
    public function bySystem(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => null,
            'user_id' => null,
        ]);
    }
}
