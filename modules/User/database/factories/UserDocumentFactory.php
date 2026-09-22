<?php

declare(strict_types=1);

namespace Modules\User\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Enum\DocumentType;
use Modules\User\Models\UserDocument;

class UserDocumentFactory extends Factory
{
    protected $model = UserDocument::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'document_type' => $this->faker->randomElement(DocumentType::cases()),
            'document_number' => $this->faker->bothify('??######'),
            'file_path' => 'documents/users/'.$this->faker->uuid.'.jpg',
            'expiry_date' => $this->faker->dateTimeBetween('now', '+5 years'),
        ];
    }
}
