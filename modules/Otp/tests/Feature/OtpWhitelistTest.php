<?php

namespace Modules\Otp\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Otp\Enum\ContactType;
use Modules\Otp\Models\OtpWhitelist;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OtpWhitelistTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([ValidateCsrfToken::class]);

        foreach (['View OTP Whitelist', 'Create OTP Whitelist', 'Edit OTP Whitelist', 'Delete OTP Whitelist'] as $perm) {
            Permission::updateOrCreate(
                ['name' => $perm, 'guard_name' => 'web'],
                ['module_name' => 'Otp']
            );
        }

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->givePermissionTo(['View OTP Whitelist', 'Create OTP Whitelist', 'Edit OTP Whitelist', 'Delete OTP Whitelist']);
    }

    // --- Index ---

    public function test_admin_can_list_whitelist_entries(): void
    {
        OtpWhitelist::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.otp-whitelist.index'));

        $response->assertOk();
        $response->assertViewIs('otp::whitelist.index');
    }

    public function test_user_without_view_permission_cannot_list(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)
            ->get(route('admin.otp-whitelist.index'));

        $response->assertForbidden();
    }

    // --- Store ---

    public function test_admin_can_create_whitelist_entry(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.otp-whitelist.store'), [
                'recipient_type' => 'email',
                'recipient' => 'test@example.com',
                'fixed_otp' => '123456',
                'is_active' => true,
                'description' => 'Test entry',
            ]);

        $response->assertRedirect(route('admin.otp-whitelist.index'));
        $this->assertDatabaseHas('otp_whitelists', [
            'recipient_type' => 'email',
            'recipient' => 'test@example.com',
            'fixed_otp' => '123456',
        ]);
    }

    public function test_cannot_create_duplicate_whitelist_entry(): void
    {
        OtpWhitelist::factory()->email()->create(['recipient' => 'test@example.com']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.otp-whitelist.store'), [
                'recipient_type' => 'email',
                'recipient' => 'test@example.com',
                'fixed_otp' => '999999',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This recipient is already whitelisted.');
        $this->assertEquals(1, OtpWhitelist::where('recipient', 'test@example.com')->count());
    }

    public function test_store_validates_fixed_otp_format(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.otp-whitelist.store'), [
                'recipient_type' => 'email',
                'recipient' => 'test@example.com',
                'fixed_otp' => 'abc123',
            ]);

        $response->assertSessionHasErrors('fixed_otp');
    }

    // --- Update ---

    public function test_admin_can_update_whitelist_entry(): void
    {
        $whitelist = OtpWhitelist::factory()->email()->create();

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.otp-whitelist.update', $whitelist), [
                'fixed_otp' => '654321',
                'is_active' => false,
            ]);

        $response->assertRedirect(route('admin.otp-whitelist.index'));
        $this->assertDatabaseHas('otp_whitelists', [
            'id' => $whitelist->id,
            'fixed_otp' => '654321',
            'is_active' => false,
        ]);
    }

    public function test_cannot_update_entry_to_duplicate_recipient(): void
    {
        OtpWhitelist::factory()->email()->create(['recipient' => 'existing@example.com']);
        $whitelist = OtpWhitelist::factory()->email()->create(['recipient' => 'other@example.com']);

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.otp-whitelist.update', $whitelist), [
                'recipient_type' => 'email',
                'recipient' => 'existing@example.com',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Another entry already exists for this recipient.');
    }

    // --- Destroy ---

    public function test_admin_can_delete_whitelist_entry(): void
    {
        $whitelist = OtpWhitelist::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.otp-whitelist.destroy', $whitelist));

        $response->assertRedirect(route('admin.otp-whitelist.index'));
        $this->assertDatabaseMissing('otp_whitelists', ['id' => $whitelist->id]);
    }

    // --- ContactType Enum ---

    public function test_whitelist_recipient_type_is_cast_to_contact_type_enum(): void
    {
        $whitelist = OtpWhitelist::factory()->email()->create();

        $this->assertInstanceOf(ContactType::class, $whitelist->fresh()->recipient_type);
        $this->assertEquals(ContactType::Email, $whitelist->fresh()->recipient_type);
    }
}
