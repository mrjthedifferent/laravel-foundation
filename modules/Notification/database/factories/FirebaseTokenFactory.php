<?php

declare(strict_types=1);

namespace Modules\Notification\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Models\FirebaseToken;

/**
 * @extends Factory<FirebaseToken>
 */
class FirebaseTokenFactory extends Factory
{
    protected $model = FirebaseToken::class;

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
            'device_id' => $this->faker->uuid(),
        ];
    }
}
