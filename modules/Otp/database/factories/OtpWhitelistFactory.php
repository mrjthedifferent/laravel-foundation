<?php

namespace Modules\Otp\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Otp\Models\OtpWhitelist;

class OtpWhitelistFactory extends Factory
{
    protected $model = OtpWhitelist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recipientType = $this->faker->randomElement(['email', 'phone']);

        return [
            'recipient_type' => $recipientType,
            'recipient' => $recipientType === 'email'
                ? $this->faker->safeEmail()
                : $this->faker->e164PhoneNumber(),
            'fixed_otp' => $this->faker->numerify('######'),
            'is_active' => true,
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * Indicate that the whitelist entry is for an email.
     */
    public function email(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'recipient_type' => 'email',
                'recipient' => $this->faker->safeEmail(),
            ];
        });
    }

    /**
     * Indicate that the whitelist entry is for a phone number.
     */
    public function phone(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'recipient_type' => 'phone',
                'recipient' => $this->faker->e164PhoneNumber(),
            ];
        });
    }

    /**
     * Indicate that the whitelist entry is inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
