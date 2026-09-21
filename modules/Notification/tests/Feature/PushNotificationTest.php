<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Modules\Notification\Jobs\BroadcastNotificationJob;
use Modules\Notification\Models\PushNotification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            ValidateCsrfToken::class,
        ]);

        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Permission::create(['name' => 'Create Push Notification', 'guard_name' => 'web', 'module_name' => 'Notification']);
        $role->givePermissionTo('Create Push Notification');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole($role);
    }

    public function test_sends_to_specific_user_on_database_and_fcm(): void
    {
        Bus::fake();
        $target = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('admin.push.notification.store'), [
            'title' => 'Hello',
            'body' => 'Test message',
            'user_id' => $target->id,
        ]);

        $response->assertRedirect(route('admin.push.notification.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('push_notifications', [
            'title' => 'Hello',
            'recipient_type' => 'specific',
            'user_id' => $target->id,
        ]);

        Bus::assertDispatched(BroadcastNotificationJob::class, function ($job) use ($target) {
            return $job->userId === $target->id
                && in_array('database', $job->channels, true)
                && in_array('fcm', $job->channels, true);
        });
    }

    /**
     * The list eager-loaded `first_name`/`last_name` from users long after those columns
     * were merged into `name`, so the page threw on any install with a notification.
     */
    public function test_the_list_page_shows_who_a_notification_went_to(): void
    {
        Permission::create(['name' => 'View Push Notification', 'guard_name' => 'web', 'module_name' => 'Notification']);
        $this->admin->givePermissionTo('View Push Notification');

        $recipient = User::factory()->create(['name' => 'Rahim Uddin', 'is_active' => true]);
        PushNotification::factory()->create(['user_id' => $recipient->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.push.notification.index'))
            ->assertOk()
            ->assertSee('Rahim Uddin');
    }

    public function test_user_id_is_required(): void
    {
        Bus::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.push.notification.store'), [
                'title' => 'Hello',
                'body' => 'Test message',
            ])
            ->assertSessionHasErrors('user_id');

        Bus::assertNotDispatched(BroadcastNotificationJob::class);
    }
}
