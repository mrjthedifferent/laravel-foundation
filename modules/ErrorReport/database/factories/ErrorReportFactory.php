<?php

namespace Modules\ErrorReport\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ErrorReport\Models\ErrorReport;

/**
 * @extends Factory<ErrorReport>
 */
class ErrorReportFactory extends Factory
{
    protected $model = ErrorReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exceptionClass = 'Exception';
        $file = fake()->filePath();
        $line = fake()->numberBetween(1, 500);
        $fingerprint = hash('sha256', $exceptionClass.$file.$line);

        return [
            'uuid' => str()->uuid()->toString(),
            'fingerprint' => $fingerprint,
            'exception_class' => $exceptionClass,
            'message' => fake()->sentence(),
            'file' => $file,
            'line' => $line,
            'trace' => [],
            'request_method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'request_path' => fake()->slug(2),
            'request_url' => fake()->url(),
            'user_id' => null,
            'context' => [],
            'occurrences' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'last_notified_at' => null,
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => now(),
        ]);
    }
}
