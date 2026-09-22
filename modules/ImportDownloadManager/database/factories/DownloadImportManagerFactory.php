<?php

namespace Modules\ImportDownloadManager\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;

/**
 * @extends Factory<DownloadImportManager>
 */
class DownloadImportManagerFactory extends Factory
{
    protected $model = DownloadImportManager::class;

    /**
     * Define the model's default state: a pending export with no file yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->words(3, true),
            'url' => null,
            'remarks' => null,
            'status' => ImportStatus::Pending,
            'type' => ImportType::Download,
        ];
    }

    /**
     * An uploaded import file awaiting processing.
     */
    public function import(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ImportType::Import,
            'url' => 'uploads/imports/'.$this->faker->uuid().'.xlsx',
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportStatus::Processing,
        ]);
    }

    public function completed(?string $url = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportStatus::Completed,
            'url' => $url ?? 'exports/'.$this->faker->uuid().'.xlsx',
            'remarks' => 'completed',
        ]);
    }

    public function failed(?string $remarks = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportStatus::Failed,
            'remarks' => $remarks ?? $this->faker->sentence(),
        ]);
    }
}
