<?php

namespace Mrj\Foundation\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mrj\Foundation\Tests\TestCase;

class HasImageAttributeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Before HasImageAttribute became a real Eloquent attribute cast rather
     * than a getAttribute()/setAttribute() override, $user->image (property
     * access) returned the transformed URL while $user->toArray()['image']
     * returned the untransformed raw path — the two disagreed because
     * attributesToArray() reads $attributes directly and never called the
     * overridden getAttribute().
     */
    public function test_property_access_and_array_serialization_agree_on_the_image_url(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $user->image = UploadedFile::fake()->image('avatar.jpg');
        $user->save();

        $this->assertSame($user->image, $user->toArray()['image']);
        $this->assertStringContainsString('storage/', $user->image);
    }

    public function test_setting_the_image_to_null_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $user->image = UploadedFile::fake()->image('avatar.jpg');
        $user->save();
        $storedPath = $user->getRawOriginal('image');
        Storage::disk('public')->assertExists($storedPath);

        $user->image = null;
        $user->save();

        Storage::disk('public')->assertMissing($storedPath);
        $this->assertNull($user->getRawOriginal('image'));
    }
}
