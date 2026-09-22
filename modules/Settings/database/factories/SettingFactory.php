<?php

namespace Modules\Settings\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Settings\Models\Setting;

/**
 * Setting's `value` mutator depends on `type` (arrays are imploded, JSON is
 * encoded, "encrypted" values are encrypted), so every state sets `type`
 * before `value` — attributes are filled in array order.
 *
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state: a plain text setting.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->lexify('setting_????????'),
            'group' => 'General',
            'type' => 'text',
            'value' => $this->faker->words(3, true),
            'options' => null,
            'description' => $this->faker->sentence(),
            'is_visible' => true,
            'is_required' => false,
        ];
    }

    public function boolean(bool $value = true): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => $value ? '1' : '0',
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
    public function json(array $value = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'json',
            'value' => $value,
        ]);
    }

    /**
     * A secret, stored encrypted by the model's SecretCipher mutator.
     */
    public function encrypted(?string $plaintext = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'encrypted',
            'value' => $plaintext ?? $this->faker->sha1(),
        ]);
    }

    /**
     * A dropdown setting; `value` must be one of the option keys.
     *
     * @param  array<string, string>  $options
     */
    public function select(array $options = ['a' => 'A', 'b' => 'B'], ?string $value = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'select',
            'value' => $value ?? array_key_first($options),
            'options' => json_encode($options, JSON_THROW_ON_ERROR),
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }
}
