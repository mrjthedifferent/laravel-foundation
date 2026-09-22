<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
    }

    public function test_updating_the_password_clears_the_must_change_password_flag(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'old-password',
            'password' => 'a-new-strong-password',
            'password_confirmation' => 'a-new-strong-password',
        ])->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('a-new-strong-password', $user->password));
    }
}
