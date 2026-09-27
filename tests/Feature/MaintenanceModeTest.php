<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Mrj\Foundation\Tests\TestCase;
use Override;

class MaintenanceModeTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);
    }

    private function maintenance(bool $on, string $message = 'Back at noon.'): void
    {
        config([
            'settings.maintenance_mode.value' => $on,
            'settings.maintenance_message.value' => $message,
        ]);
    }

    public function test_it_is_off_by_default(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.profile.edit'))->assertOk();
    }

    public function test_a_user_gets_the_maintenance_page_with_its_message(): void
    {
        $this->maintenance(true);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.profile.edit'))
            ->assertStatus(503)
            ->assertSee('Back at noon.')
            ->assertSee(route('logout'), false);

        $this->actingAs(User::factory()->create())->getJson(route('admin.profile.edit'))
            ->assertStatus(503)->assertJsonPath('message', 'Back at noon.');
    }

    public function test_a_user_can_still_sign_out(): void
    {
        $this->maintenance(true);

        $this->actingAs(User::factory()->create())->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_a_super_admin_passes_and_sees_a_notice(): void
    {
        $this->maintenance(true);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee(__('foundation::foundation.navbar.maintenance_on'));
    }

    public function test_a_guest_can_reach_the_sign_in_page(): void
    {
        $this->maintenance(true);

        $this->get(route('login'))->assertOk();
    }
}
