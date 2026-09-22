<?php

namespace Modules\User\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Models\UserLoginHistory;

/**
 * @extends Factory<UserLoginHistory>
 */
class UserLoginHistoryFactory extends Factory
{
    protected $model = UserLoginHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'logged_in_at' => now(),
            'logged_out_at' => null,
        ];
    }

    public function loggedOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'logged_out_at' => now(),
        ]);
    }
}
