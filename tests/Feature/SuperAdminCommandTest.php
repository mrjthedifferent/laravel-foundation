<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Mrj\Foundation\Tests\TestCase;

class SuperAdminCommandTest extends TestCase
{
    public function test_it_creates_a_super_admin_interactively(): void
    {
        $this->artisan('foundation:super-admin')
            ->expectsQuestion('Email (or phone) of the Super Admin', 'owner@example.com')
            ->expectsQuestion('Name', 'Owner')
            ->expectsQuestion('Password (at least 8 characters)', 'a-strong-password')
            ->expectsQuestion('Password again', 'a-strong-password')
            ->assertSuccessful();

        $user = User::where('email', 'owner@example.com')->sole();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('a-strong-password', $user->password));
        $this->assertSame([], $user->getRoleNames()->all());
    }

    public function test_it_creates_one_without_prompts_for_scripts_and_ci(): void
    {
        $this->artisan('foundation:super-admin', ['login' => 'ci@example.com', '--name' => 'CI', '--password' => 'a-strong-password', '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertTrue(User::where('email', 'ci@example.com')->sole()->isSuperAdmin());
    }

    public function test_it_refuses_mismatched_or_weak_passwords(): void
    {
        $this->artisan('foundation:super-admin', ['login' => 'owner@example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password (at least 8 characters)', 'a-strong-password')
            ->expectsQuestion('Password again', 'something-else')
            ->assertFailed();

        $this->artisan('foundation:super-admin', ['login' => 'owner@example.com', '--name' => 'Owner', '--password' => 'short'])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }

    public function test_it_promotes_an_existing_user_by_email_or_phone(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.com', 'phone' => '+8801712345678']);

        $this->artisan('foundation:super-admin', ['login' => '01712345678'])
            ->expectsConfirmation("Make $user->name (staff@example.com) a Super Admin? They will pass every permission check.", 'yes')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->isSuperAdmin());
    }

    public function test_it_revokes_and_warns_before_removing_the_last_one(): void
    {
        $first = User::factory()->superAdmin()->create();
        $second = User::factory()->superAdmin()->create();

        $this->artisan('foundation:super-admin', ['login' => $first->email, '--revoke' => true])
            ->expectsConfirmation("Revoke Super Admin from $first->name ($first->email)?", 'yes')
            ->assertSuccessful();
        $this->assertFalse($first->fresh()->isSuperAdmin());

        $this->artisan('foundation:super-admin', ['login' => $second->email, '--revoke' => true])
            ->expectsOutputToContain('is the last Super Admin')
            ->expectsConfirmation("Revoke Super Admin from $second->name ($second->email)?", 'no')
            ->assertFailed();
        $this->assertTrue($second->fresh()->isSuperAdmin());
    }

    public function test_it_lists_the_super_admins(): void
    {
        $this->artisan('foundation:super-admin', ['--list' => true])->expectsOutputToContain('There is no Super Admin')->assertSuccessful();

        User::factory()->superAdmin()->create(['name' => 'Listed Owner']);

        $this->artisan('foundation:super-admin', ['--list' => true])->expectsOutputToContain('Listed Owner')->assertSuccessful();
    }
}
