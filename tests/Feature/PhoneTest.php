<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Schema;
use Mrj\Foundation\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PhoneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([ValidateCsrfToken::class, ThrottleRequests::class]);
        config(['settings.phone_country_codes.value' => ['+880']]);
        User::$lockedOut = [];
    }

    private function admin(): User
    {
        $permissions = ['View User', 'Create User', 'Edit User', 'Verify User Contact', 'Assign Permission'];

        foreach ($permissions as $name) {
            Permission::updateOrCreate(['name' => $name, 'guard_name' => 'web'], ['module_name' => 'User']);
        }

        $admin = User::factory()->create();
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    public function test_users_table_has_the_phone_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['phone', 'phone_verified_at']));
    }

    public function test_phone_is_stored_in_e164_however_it_is_typed(): void
    {
        $this->assertSame('+8801712345678', User::factory()->create(['phone' => '01712345678'])->phone);
        $this->assertSame('+8801812345678', User::factory()->create(['phone' => '8801812345678'])->phone);
        $this->assertSame('+8801912345678', User::factory()->create(['phone' => '+880 1912-345678'])->phone);
        $this->assertNull(User::factory()->create(['phone' => null])->phone);
        $this->assertNull(User::factory()->create(['phone' => ''])->phone);
    }

    public function test_where_phone_accepts_any_typed_form_and_ignores_non_phones(): void
    {
        $user = User::factory()->create(['phone' => '+8801712345678']);

        $this->assertTrue(User::query()->wherePhone('01712345678')->first()->is($user));
        $this->assertTrue(User::query()->wherePhone('+8801712345678')->first()->is($user));
        $this->assertSame(0, User::query()->wherePhone(null)->count());
        $this->assertSame(0, User::query()->wherePhone('someone@example.com')->count());
    }

    public function test_user_signs_in_with_a_phone_number(): void
    {
        $user = User::factory()->create(['phone' => '+8801712345678', 'password' => 'secret-password']);

        $this->post(route('login'), ['login' => '01712345678', 'password' => 'secret-password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_with_a_phone_number_is_refused(): void
    {
        User::factory()->create(['phone' => '+8801712345678', 'password' => 'secret-password']);

        $this->post(route('login'), ['login' => '01712345678', 'password' => 'nope']);
        $this->assertGuest();
    }

    public function test_api_login_accepts_a_phone_number(): void
    {
        User::factory()->create(['phone' => '+8801712345678', 'password' => 'secret-password']);

        $this->postJson('/api/v1/login', ['id' => '8801712345678', 'password' => 'secret-password'])
            ->assertOk()
            ->assertJsonPath('data.user.phone', '+8801712345678');
    }

    public function test_admin_creates_and_updates_a_user_with_a_unique_phone(): void
    {
        $admin = $this->admin();
        $role = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $payload = ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'secret-123', 'password_confirmation' => 'secret-123', 'roles' => [$role->id]];

        $this->actingAs($admin)->post(route('admin.users.store'), [...$payload, 'phone' => '+8801712345678'])->assertSessionHasNoErrors();
        $jane = User::where('email', 'jane@example.com')->sole();
        $this->assertSame('+8801712345678', $jane->phone);

        // The same number typed differently is still a duplicate.
        $this->actingAs($admin)
            ->post(route('admin.users.store'), [...$payload, 'email' => 'other@example.com', 'phone' => '8801712345678'])
            ->assertSessionHasErrors('phone');

        // A user keeps their own number on update, and can change it.
        $update = ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'roles' => [$role->id]];
        $this->actingAs($admin)->put(route('admin.users.update', $jane), [...$update, 'phone' => '+8801712345678'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->put(route('admin.users.update', $jane), [...$update, 'phone' => '8801812345678'])->assertSessionHasNoErrors();
        $this->assertSame('+8801812345678', $jane->fresh()->phone);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [...$payload, 'email' => 'bad@example.com', 'phone' => 'not-a-number'])
            ->assertSessionHasErrors('phone');
    }

    public function test_admin_verifies_a_phone_and_pages_show_it(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Phone Person', 'phone' => '+8801712345678']);

        $this->actingAs($admin)->get(route('admin.users.index', ['search' => '1712345']))->assertOk()->assertSee('Phone Person')->assertSee('+8801712345678');
        $this->actingAs($admin)->get(route('admin.users.index', ['phone_verified' => 1]))->assertOk()->assertDontSee('Phone Person');
        $this->actingAs($admin)->get(route('admin.users.show', $user))->assertOk()->assertSee('Verify Phone');
        $this->actingAs($admin)->get(route('admin.users.edit', $user))->assertOk()->assertSee('+8801712345678');
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk()->assertSee('Mobile No');

        $this->actingAs($admin)->post(route('admin.users.verify.phone', $user))->assertSessionHas('success');
        $this->assertNotNull($user->fresh()->phone_verified_at);

        $this->actingAs($admin)->get(route('admin.users.index', ['phone_verified' => 1]))->assertOk()->assertSee('Phone Person');
    }

    public function test_a_phone_verified_user_passes_the_verified_middleware(): void
    {
        $user = User::factory()->create(['email_verified_at' => null, 'phone' => '+8801712345678']);

        // The notification pages sit behind the `verified` middleware.
        $this->actingAs($user)->get(route('admin.notification.index'))->assertRedirect(route('verification.notice'));

        $user->forceFill(['phone_verified_at' => now()])->save();
        $this->actingAs($user)->get(route('admin.notification.index'))->assertOk();
    }

    public function test_bulk_upload_sample_has_the_phone_column(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.users.bulk.sample'));

        $response->assertOk();
        $this->assertStringContainsString('users_sample.xlsx', (string) $response->headers->get('content-disposition'));
    }
}
