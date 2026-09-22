<?php

namespace Modules\User\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    /**
     * Before this fix, 'image' => ['nullable'] accepted any uploaded file with
     * no type or size restriction, so an arbitrary (and arbitrarily large) file
     * could be written to public storage under the user's account.
     */
    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/v1/profile', [
            'image' => UploadedFile::fake()->create('payload.php', 10),
        ])->assertJsonValidationErrors('image');
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/v1/profile', [
            'image' => UploadedFile::fake()->image('avatar.jpg')->size(3000),
        ])->assertJsonValidationErrors('image');
    }

    public function test_a_valid_image_is_accepted(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/v1/profile', [
            'image' => UploadedFile::fake()->image('avatar.jpg')->size(500),
        ])->assertOk();
    }
}
