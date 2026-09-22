<?php

namespace Modules\User\Tests\Unit\Actions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Modules\Notification\Notifications\AppNotification;
use Modules\User\Actions\MarkContactVerifiedAction;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Data\UserData;
use Modules\User\Events\UserRolesChanged;
use Modules\User\Events\UserUpdated;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateUserActionTest extends TestCase
{
    use RefreshDatabase;

    private Role $member;

    private Role $reviewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = Role::query()->create(['name' => 'Member', 'guard_name' => 'web']);
        $this->reviewer = Role::query()->create(['name' => 'Reviewer', 'guard_name' => 'web']);
    }

    public function test_it_updates_the_given_fields_and_keeps_the_rest(): void
    {
        $user = User::factory()->create(['name' => 'Old', 'gender' => 'female']);

        $updated = app(UpdateUserAction::class)->execute($user->id, new UserData(name: 'New', roles: null));

        $this->assertSame('New', $updated->name);
        $this->assertSame($user->email, $updated->email);
        $this->assertSame('female', $user->fresh()->getRawOriginal('gender'));
    }

    public function test_null_roles_leave_the_roles_untouched(): void
    {
        Event::fake([UserRolesChanged::class, UserUpdated::class]);
        $user = User::factory()->create();
        $user->assignRole($this->member);

        app(UpdateUserAction::class)->execute($user->id, new UserData(name: 'Renamed', roles: null));

        $this->assertSame(['Member'], $user->fresh()->getRoleNames()->all());
        Event::assertNotDispatched(UserRolesChanged::class);
        Event::assertDispatched(UserUpdated::class);
    }

    /**
     * UserData::from() with only some keys, as the status toggle and the API profile
     * update build it. Roles and status used to default to [] and true, so this
     * removed every role and reactivated a deactivated user.
     */
    public function test_a_partial_update_keeps_roles_and_status(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole($this->member);

        app(UpdateUserAction::class)->execute($user->id, UserData::from(['name' => 'Renamed']));

        $user = $user->fresh();
        $this->assertSame(['Member'], $user->getRoleNames()->all());
        $this->assertFalse((bool) $user->is_active);
    }

    public function test_new_roles_replace_the_old_ones_and_fire_roles_changed(): void
    {
        Event::fake([UserRolesChanged::class]);
        $user = User::factory()->create();
        $user->assignRole($this->member);

        app(UpdateUserAction::class)->execute($user->id, new UserData(roles: [$this->reviewer->id]));

        $this->assertSame(['Reviewer'], $user->fresh()->getRoleNames()->all());
        Event::assertDispatched(
            UserRolesChanged::class,
            fn (UserRolesChanged $event): bool => $event->oldRoles === ['Member'] && $event->newRoles === ['Reviewer'],
        );
    }

    public function test_resubmitting_the_same_roles_does_not_fire_roles_changed(): void
    {
        Event::fake([UserRolesChanged::class]);
        $user = User::factory()->create();
        $user->assignRole($this->member);

        app(UpdateUserAction::class)->execute($user->id, new UserData(roles: [$this->member->id]));

        $this->assertSame(['Member'], $user->fresh()->getRoleNames()->all());
        Event::assertNotDispatched(UserRolesChanged::class);
    }

    public function test_an_empty_roles_list_clears_all_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole($this->member);

        app(UpdateUserAction::class)->execute($user->id, new UserData(roles: []));

        $this->assertSame([], $user->fresh()->getRoleNames()->all());
    }

    public function test_deactivation_notifies_the_user_once(): void
    {
        Notification::fake();
        $active = User::factory()->create(['is_active' => true]);
        $alreadyInactive = User::factory()->inactive()->create();

        app(UpdateUserAction::class)->execute($active->id, new UserData(roles: null, is_active: false));
        app(UpdateUserAction::class)->execute($alreadyInactive->id, new UserData(roles: null, is_active: false));

        $this->assertFalse($active->fresh()->is_active);
        Notification::assertSentToTimes($active, AppNotification::class, 1);
        Notification::assertNothingSentTo($alreadyInactive);
    }

    public function test_mark_contact_verified_stamps_the_matching_column(): void
    {
        $user = User::factory()->create(['email_verified_at' => null, 'phone' => '+8801712345678']);
        $action = app(MarkContactVerifiedAction::class);

        $action->execute($user, 'phone');
        $this->assertNotNull($user->fresh()->phone_verified_at);
        $this->assertNull($user->fresh()->email_verified_at);

        $action->execute($user, 'email');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_mark_contact_verified_rejects_an_unknown_contact_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(MarkContactVerifiedAction::class)->execute(User::factory()->create(), 'fax');
    }
}
