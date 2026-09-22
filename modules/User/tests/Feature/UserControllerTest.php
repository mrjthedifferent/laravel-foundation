<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Modules\ActivityLog\Models\Device;
use Modules\ImportDownloadManager\Enum\ImportStatus;
use Modules\ImportDownloadManager\Enum\ImportType;
use Modules\ImportDownloadManager\Models\DownloadImportManager;
use Modules\Notification\Models\FirebaseToken;
use Modules\Notification\Notifications\AppNotification;
use Modules\User\Jobs\UserBulkUploadJob;
use Modules\User\Jobs\UserExportJob;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The admin user-management screens: list, create, edit, delete, status
 * toggle, manual contact verification, password reset, account reset, and
 * the queued export / bulk upload.
 */
class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private const array ADMIN_PERMISSIONS = [
        'View User', 'Create User', 'Edit User', 'Delete User',
        'User Password Reset', 'Verify User Contact', 'Assign Permission',
    ];

    private User $admin;

    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);

        foreach (self::ADMIN_PERMISSIONS as $name) {
            Permission::updateOrCreate(['name' => $name, 'guard_name' => 'web'], ['module_name' => 'User']);
        }

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->givePermissionTo(self::ADMIN_PERMISSIONS);

        $this->role = Role::firstOrCreate(['name' => 'Member', 'guard_name' => 'web']);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo($permissions);

        return $user;
    }

    // ── index ─────────────────────────────────────────────────────────────────

    public function test_index_lists_users_and_filters_by_status(): void
    {
        User::factory()->create(['name' => 'Active Person']);
        User::factory()->inactive()->create(['name' => 'Dormant Person']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Active Person')
            ->assertSee('Dormant Person');

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['is_active' => 0]))
            ->assertOk()
            ->assertSee('Dormant Person')
            ->assertDontSee('Active Person');
    }

    public function test_index_is_forbidden_without_view_user(): void
    {
        $this->actingAs($this->userWith([]))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_a_user_with_the_chosen_roles(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Person',
                'email' => 'new.person@example.com',
                'password' => 'secret-123',
                'password_confirmation' => 'secret-123',
                'roles' => [$this->role->id],
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $user = User::where('email', 'new.person@example.com')->sole();
        $this->assertSame('New Person', $user->name);
        $this->assertTrue($user->hasRole('Member'));
    }

    public function test_store_requires_the_assign_permission_permission_too(): void
    {
        $this->actingAs($this->userWith(['Create User']))
            ->post(route('admin.users.store'), [
                'name' => 'New Person',
                'email' => 'new.person@example.com',
                'password' => 'secret-123',
                'password_confirmation' => 'secret-123',
                'roles' => [$this->role->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new.person@example.com']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'roles']);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_update_changes_profile_fields_and_roles(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $user->assignRole($this->role);
        $other = Role::firstOrCreate(['name' => 'Reviewer', 'guard_name' => 'web']);

        $this->actingAs($this->admin)
            ->from(route('admin.users.edit', $user))
            ->put(route('admin.users.update', $user), [
                'name' => 'New Name',
                'email' => $user->email,
                'roles' => [$other->id],
            ])
            ->assertRedirect(route('admin.users.edit', $user))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame(['Reviewer'], $user->getRoleNames()->all());
    }

    public function test_update_keeps_the_active_flag_when_it_is_not_submitted(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Still Inactive',
                'email' => $user->email,
                'roles' => [$this->role->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_changing_roles_requires_the_assign_permission_permission(): void
    {
        $user = User::factory()->create(['name' => 'Untouched']);

        $this->actingAs($this->userWith(['Edit User']))
            ->put(route('admin.users.update', $user), [
                'name' => 'Changed',
                'email' => $user->email,
                'roles' => [$this->role->id],
            ])
            ->assertForbidden();

        $this->assertSame('Untouched', $user->fresh()->name);
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_deletes_the_user_with_their_tokens_and_devices(): void
    {
        $user = User::factory()->create();
        FirebaseToken::factory()->for($user)->create();
        Device::factory()->for($user)->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($user);
        $this->assertDatabaseMissing('firebase_tokens', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('devices', ['user_id' => $user->id]);
    }

    public function test_an_admin_cannot_delete_themselves(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->admin))
            ->assertForbidden();

        $this->assertModelExists($this->admin);
    }

    public function test_destroy_is_forbidden_without_delete_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->userWith(['View User', 'Edit User']))
            ->delete(route('admin.users.destroy', $user))
            ->assertForbidden();

        $this->assertModelExists($user);
    }

    // ── status ────────────────────────────────────────────────────────────────

    public function test_deactivating_a_user_notifies_them(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.status', $user), ['is_active' => 0])
            ->assertSessionHas('success');

        $this->assertFalse($user->fresh()->is_active);
        Notification::assertSentTo($user, AppNotification::class);
    }

    public function test_activating_a_user_does_not_send_the_deactivation_notice(): void
    {
        Notification::fake();
        $user = User::factory()->inactive()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.users.status', $user), ['is_active' => 1])
            ->assertSessionHas('success');

        $this->assertTrue($user->fresh()->is_active);
        Notification::assertNothingSentTo($user);
    }

    public function test_deactivating_a_user_keeps_their_roles(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($this->role);

        $this->actingAs($this->admin)
            ->post(route('admin.users.status', $user), ['is_active' => 0])
            ->assertSessionHas('success');

        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame(['Member'], $user->fresh()->getRoleNames()->all());
    }

    public function test_status_requires_a_boolean(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.users.status', $user), [])
            ->assertSessionHasErrors('is_active');
    }

    public function test_an_admin_cannot_change_their_own_status(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.status', $this->admin), ['is_active' => 0])
            ->assertForbidden();

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    // ── verify email ──────────────────────────────────────────────────────────

    public function test_admin_verifies_an_unverified_email(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.verify.email', $user))
            ->assertSessionHas('success');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verifying_an_already_verified_email_only_informs(): void
    {
        $verifiedAt = now()->subYear()->startOfSecond();
        $user = User::factory()->create(['email_verified_at' => $verifiedAt]);

        $this->actingAs($this->admin)
            ->post(route('admin.users.verify.email', $user))
            ->assertSessionHas('info');

        $this->assertTrue($user->fresh()->email_verified_at->equalTo($verifiedAt));
    }

    public function test_verify_email_is_forbidden_without_the_permission(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($this->userWith(['View User']))
            ->post(route('admin.users.verify.email', $user))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    // ── password reset ────────────────────────────────────────────────────────

    public function test_reset_password_sets_a_new_password_and_forces_a_change(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'original-password']);
        $originalHash = $user->password;

        $this->actingAs($this->admin)
            ->get(route('admin.user.password.reset', $user))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNotSame($originalHash, $user->password);
        $this->assertTrue($user->must_change_password);
        Notification::assertSentTo($user, AppNotification::class);
    }

    public function test_an_admin_cannot_reset_their_own_password_here(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.user.password.reset', $this->admin))
            ->assertForbidden();
    }

    // ── manage account ────────────────────────────────────────────────────────

    public function test_account_reset_clears_the_profile_and_devices_but_keeps_the_user(): void
    {
        $user = User::factory()->create(['name' => 'Some Name', 'gender' => 'male']);
        FirebaseToken::factory()->count(2)->for($user)->create();
        Device::factory()->for($user)->create();

        $this->actingAs($this->admin)
            ->post(route('admin.users.account.manage', $user), ['action' => 'reset'])
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->name);
        $this->assertNull($user->gender);
        $this->assertSame(0, FirebaseToken::where('user_id', $user->id)->count());
        $this->assertSame(0, Device::where('user_id', $user->id)->count());
    }

    public function test_account_delete_removes_the_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.users.account.manage', $user), ['action' => 'delete'])
            ->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($user);
    }

    public function test_manage_account_rejects_an_unknown_action(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.users.account.manage', $user), ['action' => 'archive'])
            ->assertSessionHasErrors('action');

        $this->assertModelExists($user);
    }

    // ── export / bulk upload ──────────────────────────────────────────────────

    public function test_export_records_a_pending_download_and_queues_the_job(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->get(route('admin.users.export', ['search' => 'someone']))
            ->assertSessionHas('success');

        $this->assertSame(1, DownloadImportManager::count());
        $this->assertDatabaseHas('download_import_managers', [
            'user_id' => $this->admin->id,
            'type' => ImportType::Download->value,
            'status' => ImportStatus::Pending->value,
        ]);
        Queue::assertPushed(UserExportJob::class);
    }

    public function test_bulk_upload_stores_the_file_and_queues_the_import(): void
    {
        Queue::fake();
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('admin.users.bulk'), [
                'users' => UploadedFile::fake()->create('users.xlsx', 8, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('download_import_managers', ['type' => ImportType::Import->value]);
        $url = (string) DownloadImportManager::query()->sole()->getAttribute('url');
        $this->assertStringStartsWith('uploads/users/', $url);
        Storage::disk('public')->assertExists($url);
        Queue::assertPushed(UserBulkUploadJob::class);
    }

    public function test_bulk_upload_rejects_a_non_spreadsheet(): void
    {
        Queue::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.users.bulk'), [
                'users' => UploadedFile::fake()->create('users.pdf', 8, 'application/pdf'),
            ])
            ->assertSessionHasErrors('users');

        Queue::assertNothingPushed();
        $this->assertSame(0, DownloadImportManager::count());
    }
}
