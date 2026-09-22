<?php

namespace Modules\User\Tests\Unit\Actions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Actions\ChangePasswordAction;
use Tests\TestCase;

class ChangePasswordActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_a_password_clears_the_must_change_password_flag(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true])->save();

        (new ChangePasswordAction)->execute($user, 'a-new-password');

        $this->assertFalse($user->fresh()->must_change_password);
    }
}
