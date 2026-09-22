<?php

namespace Modules\User\Tests\Unit\Actions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Actions\ResetPasswordAction;
use Tests\TestCase;

class ResetPasswordActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An admin-generated password is, from the target user's point of view,
     * exactly as "known to someone else" as the seeded admin's password, so
     * it must force the same next-login change.
     */
    public function test_resetting_a_password_forces_the_user_to_change_it_on_next_login(): void
    {
        $user = User::factory()->create();
        $originalHash = $user->password;

        (new ResetPasswordAction)->execute($user);

        $user->refresh();

        $this->assertTrue($user->must_change_password);
        $this->assertNotSame($originalHash, $user->password);
    }
}
