<?php

namespace Modules\Otp\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\VerificationCode;

class VerificationCodeFactory extends Factory
{
    protected $model = VerificationCode::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contactType = $this->faker->randomElement(ContactType::cases());

        return [
            'code' => $this->faker->numerify('######'),
            'contact_type' => $contactType->value,
            'contact' => $contactType === ContactType::Email
                ? $this->faker->safeEmail()
                : $this->faker->e164PhoneNumber(),
            'expires_at' => now()->addMinutes((int) config('settings.otp_expiry_minutes.value', 10)),
            'is_verified' => false,
        ];
    }

    /**
     * Indicate the code is for an email.
     */
    public function email(): static
    {
        return $this->state(fn () => [
            'contact_type' => ContactType::Email->value,
            'contact' => $this->faker->safeEmail(),
        ]);
    }

    /**
     * Indicate the code is for a phone number.
     */
    public function phone(): static
    {
        return $this->state(fn () => [
            'contact_type' => ContactType::Phone->value,
            'contact' => $this->faker->e164PhoneNumber(),
        ]);
    }

    /**
     * Indicate the code has been verified.
     */
    public function verified(): static
    {
        return $this->state(fn () => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate the code has expired.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subMinutes(5),
        ]);
    }
}
