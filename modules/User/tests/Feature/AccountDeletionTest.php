<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Notification\Notifications\AppNotification;
use Modules\User\Actions\TrackLoginAction;
use Modules\User\Enum\DeletionStatus;
use Modules\User\Events\AccountDeleting;
use Modules\User\Exceptions\AccountDeletionBlocked;
use Modules\User\Models\UserLoginHistory;
use Modules\User\Services\AccountDeletion;
use Mrj\Foundation\Foundation;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Account deletion keeps other people's records safe: a request signs the person out, waits
 * out the grace period (and staff review when automatic deletion is off), then the account is
 * anonymized; the user row stays so payments, orders and logs keep pointing at it.
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Permission::create(['name' => AccountDeletion::REVIEW_PERMISSION, 'guard_name' => 'web', 'module_name' => 'User']);
        Permission::create(['name' => AccountDeletion::ANONYMIZE_PERMISSION, 'guard_name' => 'web', 'module_name' => 'User']);
    }

    protected function tearDown(): void
    {
        Foundation::flushAccountDeletionBlockers();

        parent::tearDown();
    }

    private function deletion(): AccountDeletion
    {
        return app(AccountDeletion::class);
    }

    private function person(): User
    {
        return User::factory()->create(['phone' => '+8801711000000', 'email' => 'person@example.com', 'password' => Hash::make('secret-pass-1')]);
    }

    private function reviewer(): User
    {
        $staff = User::factory()->create(['is_active' => true]);
        $staff->givePermissionTo(AccountDeletion::REVIEW_PERMISSION, AccountDeletion::ANONYMIZE_PERMISSION);

        return $staff;
    }

    public function test_a_request_is_scheduled_after_the_grace_period_and_signs_out_everywhere(): void
    {
        $user = $this->person();
        $user->createToken('app');

        $request = $this->deletion()->request($user);

        $this->assertSame(DeletionStatus::Scheduled, $request->status);
        $this->assertSame(now()->addDays(30)->toDateString(), $request->scheduled_for?->toDateString());
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
        $this->assertModelExists($user);
        Notification::assertSentTo($user, AppNotification::class);
    }

    public function test_asking_twice_returns_the_same_request(): void
    {
        $user = $this->person();

        $this->assertSame($this->deletion()->request($user)->id, $this->deletion()->request($user)->id);
        $this->assertDatabaseCount('account_deletion_requests', 1);
    }

    public function test_blockers_refuse_the_request(): void
    {
        Foundation::accountDeletionBlocker(fn (User $user) => 'Finish your open orders first.');
        Foundation::accountDeletionBlocker(fn (User $user) => null);
        $user = $this->person();

        try {
            $this->deletion()->request($user);
            $this->fail('The request should have been blocked.');
        } catch (AccountDeletionBlocked $e) {
            $this->assertSame(['Finish your open orders first.'], $e->blockers);
        }

        $this->assertDatabaseCount('account_deletion_requests', 0);
    }

    public function test_a_super_admin_is_always_blocked(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        $this->assertNotSame([], $this->deletion()->blockers($admin));
    }

    public function test_with_automatic_deletion_off_staff_review_first(): void
    {
        config(['foundation.account_deletion.automatic' => false]);
        $staff = $this->reviewer();
        $user = $this->person();

        $request = $this->deletion()->request($user);

        $this->assertSame(DeletionStatus::PendingReview, $request->status);
        $this->assertNull($request->scheduled_for);
        Notification::assertSentTo($staff, AppNotification::class);

        // Pending review never runs on its own.
        $this->travel(60)->days();
        $this->deletion()->runDue();
        $this->assertNull($user->fresh()->anonymized_at);

        // Approval schedules it for the later of now and requested_at + grace.
        $this->deletion()->approve($request, $staff);
        $request->refresh();
        $this->assertSame(DeletionStatus::Scheduled, $request->status);
        $this->assertSame($staff->id, $request->reviewed_by);
        $this->assertSame(now()->toDateString(), $request->scheduled_for?->toDateString());
    }

    public function test_a_rejected_request_leaves_the_account_as_it_was(): void
    {
        config(['foundation.account_deletion.automatic' => false]);
        $staff = $this->reviewer();
        $user = $this->person();
        $request = $this->deletion()->request($user);

        $this->deletion()->reject($request, $staff, 'Open dispute');

        $request->refresh();
        $this->assertSame(DeletionStatus::Rejected, $request->status);
        $this->assertSame('Open dispute', $request->reason);
        $this->assertNull($this->deletion()->open($user));
        $this->assertTrue((bool) $user->fresh()->is_active);
        Notification::assertSentTo($user, AppNotification::class, fn (AppNotification $n) => str_contains($n->body, 'Open dispute'));
    }

    public function test_signing_in_again_cancels_the_request(): void
    {
        $user = $this->person();
        $this->deletion()->request($user);

        app(TrackLoginAction::class)->execute($user, Request::create('/login', 'POST'));

        $this->assertNull($this->deletion()->open($user));
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $user->id, 'status' => 'cancelled']);
    }

    public function test_anonymizing_keeps_the_row_and_removes_every_personal_detail(): void
    {
        $user = $this->person();
        $user->assignRole(Role::create(['name' => 'Member', 'guard_name' => 'web']));
        $user->update(['name' => 'Rahim Uddin']); // leaves an audit row with the old values
        $request = $this->deletion()->request($user);
        $seen = [];
        Event::listen(AccountDeleting::class, function (AccountDeleting $e) use (&$seen): void {
            $seen[] = $e->user->phone; // apps still see the person's details here
        });

        $this->deletion()->anonymize($user, $request);

        $user = User::query()->findOrFail($user->id);
        $this->assertSame(['+8801711000000'], $seen);
        $this->assertSame('Deleted user', $user->name);
        $this->assertNull($user->phone);
        $this->assertNull($user->email);
        $this->assertFalse((bool) $user->is_active);
        $this->assertNotNull($user->anonymized_at);
        $this->assertFalse(Hash::check('secret-pass-1', $user->password));
        $this->assertCount(0, $user->roles);
        $this->assertSame(0, Audit::query()->where('auditable_type', $user->getMorphClass())->where('auditable_id', $user->id)->count());
        $this->assertSame(DeletionStatus::Done, $request->fresh()->status);

        // The phone and email are free for a new account.
        User::factory()->create(['phone' => '+8801711000000', 'email' => 'person@example.com']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_the_daily_command_anonymizes_due_requests_and_waits_on_new_blockers(): void
    {
        $staff = $this->reviewer();
        $due = $this->person();
        $blocked = User::factory()->create();
        $this->deletion()->request($due);
        $this->deletion()->request($blocked);

        $this->travel(29)->days();
        $this->artisan('accounts:purge-deleted')->assertSuccessful();
        $this->assertNull($due->fresh()->anonymized_at);

        Foundation::accountDeletionBlocker(fn (User $user) => $user->is($blocked) ? 'A new order came in.' : null);
        $this->travel(2)->days();
        $this->artisan('accounts:purge-deleted')->assertSuccessful();

        $this->assertNotNull($due->fresh()->anonymized_at);
        $this->assertNull($blocked->fresh()->anonymized_at);
        $this->assertSame(DeletionStatus::Scheduled, $this->deletion()->open($blocked)?->status);
        Notification::assertSentTo($staff, AppNotification::class, fn (AppNotification $n) => str_contains($n->body, 'something now blocks it'));
    }

    public function test_security_logs_of_anonymized_accounts_are_pruned_after_the_retention(): void
    {
        $user = $this->person();
        UserLoginHistory::create(['user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'logged_in_at' => now()]);
        $this->deletion()->anonymize($user);

        $this->travel(364)->days();
        $this->deletion()->pruneSecurityLogs();
        $this->assertSame(1, UserLoginHistory::query()->where('user_id', $user->id)->count());

        $this->travel(2)->days();
        $this->deletion()->pruneSecurityLogs();
        $this->assertSame(0, UserLoginHistory::query()->where('user_id', $user->id)->count());
    }

    public function test_the_api_reports_the_status_and_takes_the_request(): void
    {
        Foundation::accountDeletionBlocker(fn (User $user) => $user->name === 'Blocked' ? 'Settle your plan first.' : null);
        $user = $this->person();
        Sanctum::actingAs($user);
        $api = '/api/'.config('foundation.routing.api_prefix').'/account/deletion';

        $this->getJson($api)->assertOk()
            ->assertJsonPath('data.status', null)
            ->assertJsonPath('data.blockers', [])
            ->assertJsonPath('data.needs_review', false)
            ->assertJsonPath('data.grace_days', 30);

        $this->postJson($api, ['password' => 'wrong'])->assertUnauthorized();
        $this->postJson($api, ['password' => 'secret-pass-1'])->assertCreated()
            ->assertJsonPath('data.status', 'scheduled');

        $blocked = User::factory()->create(['name' => 'Blocked', 'password' => Hash::make('secret-pass-1')]);
        Sanctum::actingAs($blocked);
        $this->postJson($api, ['password' => 'secret-pass-1'])->assertStatus(422)
            ->assertJsonPath('errors.blockers', ['Settle your plan first.']);
    }

    public function test_staff_review_requests_in_administration(): void
    {
        $this->withoutMiddleware([PreventRequestForgery::class]);
        config(['foundation.account_deletion.automatic' => false]);
        $staff = $this->reviewer();
        $first = $this->deletion()->request($this->person());
        $second = $this->deletion()->request(User::factory()->create(['name' => 'Second Person']));

        $this->actingAs($staff)->get(route('admin.deletion-requests.index'))
            ->assertOk()->assertSee('Second Person')->assertSee('Automatic deletion is off');

        $this->post(route('admin.deletion-requests.approve', $first))->assertRedirect();
        $this->assertSame(DeletionStatus::Scheduled, $first->fresh()->status);

        $this->post(route('admin.deletion-requests.reject', $second), [])->assertSessionHasErrors('reason');
        $this->post(route('admin.deletion-requests.reject', $second), ['reason' => 'Under investigation'])->assertRedirect();
        $this->assertSame(DeletionStatus::Rejected, $second->fresh()->status);

        $this->get(route('admin.deletion-requests.index', ['tab' => 'history']))->assertOk()->assertSee('Under investigation');

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('admin.deletion-requests.anonymize', $first))->assertRedirect();
        $this->assertNotNull($first->user->fresh()->anonymized_at);
    }

    public function test_people_without_the_permission_cannot_open_the_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.deletion-requests.index'))
            ->assertForbidden();
    }
}
