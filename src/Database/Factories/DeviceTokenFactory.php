<?php

declare(strict_types=1);

namespace Mrj\Foundation\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Mrj\Foundation\Models\DeviceToken;

/**
 * @extends Factory<DeviceToken>
 */
class DeviceTokenFactory extends Factory
{
    protected $model = DeviceToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => $this->faker->unique()->sha256(),
            'platform' => $this->faker->randomElement(['android', 'ios']),
            'last_used_at' => now(),
        ];
    }
}
