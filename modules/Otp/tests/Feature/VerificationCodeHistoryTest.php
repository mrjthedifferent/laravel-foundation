<?php

namespace Modules\Otp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Otp\Models\VerificationCode;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VerificationCodeHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::updateOrCreate(
            ['name' => 'View Verification Code History', 'guard_name' => 'web'],
            ['module_name' => 'Otp']
        );

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->givePermissionTo('View Verification Code History');
    }

    public function test_user_with_permission_can_view_history(): void
    {
        VerificationCode::factory()->count(3)->email()->create();
        VerificationCode::factory()->count(2)->phone()->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.otp.history.index'));

        $response->assertOk();
        $response->assertViewIs('otp::history.index');
        $response->assertViewHas('codes');
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $userWithoutPermission = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('admin.otp.history.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('admin.otp.history.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_history_can_be_filtered_by_contact_type(): void
    {
        VerificationCode::factory()->count(3)->email()->create();
        VerificationCode::factory()->count(2)->phone()->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.otp.history.index', ['contact_type' => 'email']));

        $response->assertOk();

        $codes = $response->viewData('codes');
        $this->assertEquals(3, $codes->total());
    }

    public function test_history_can_be_filtered_by_verified_status(): void
    {
        VerificationCode::factory()->count(2)->verified()->create();
        VerificationCode::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.otp.history.index', ['is_verified' => '1']));

        $response->assertOk();

        $codes = $response->viewData('codes');
        $this->assertEquals(2, $codes->total());
    }

    public function test_history_can_be_searched_by_contact(): void
    {
        VerificationCode::factory()->create(['contact' => 'specific@example.com', 'contact_type' => 'email']);
        VerificationCode::factory()->count(3)->email()->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.otp.history.index', ['search' => 'specific@example.com']));

        $response->assertOk();

        $codes = $response->viewData('codes');
        $this->assertEquals(1, $codes->total());
    }

    public function test_history_shows_all_codes_by_default(): void
    {
        VerificationCode::factory()->count(5)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.otp.history.index'));

        $response->assertOk();

        $codes = $response->viewData('codes');
        $this->assertEquals(5, $codes->total());
    }

    public function test_contact_types_passed_to_view(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.otp.history.index'));

        $response->assertOk();
        $response->assertViewHas('contactTypes');

        $contactTypes = $response->viewData('contactTypes');
        $this->assertArrayHasKey('email', $contactTypes);
        $this->assertArrayHasKey('phone', $contactTypes);
    }
}
