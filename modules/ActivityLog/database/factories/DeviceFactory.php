<?php

namespace Modules\ActivityLog\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ActivityLog\Models\Device;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $os = $this->faker->randomElement(['android', 'ios']);

        return [
            'user_id' => User::factory(),
            'device_id' => $this->faker->unique()->uuid(),
            'device_label' => $this->faker->words(2, true),
            'device_type' => 'mobile',
            'os' => $os,
            'os_version' => $this->faker->numerify('##.#'),
            'model' => $this->faker->bothify('Model-??##'),
            'app_version' => $this->faker->numerify('#.#.#'),
        ];
    }

    /**
     * A device not (yet) linked to a user.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}
