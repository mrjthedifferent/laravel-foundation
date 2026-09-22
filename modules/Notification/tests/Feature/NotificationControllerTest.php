<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Models\Notification;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([PreventRequestForgery::class]);

        $this->user = User::factory()->create(['is_active' => true]);
    }

    // --- Helpers ---

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function notificationFor(User $user, array $overrides = []): Notification
    {
        return Notification::factory()->create(array_merge([
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ], $overrides));
    }

    // --- Authentication ---

    public function test_unauthenticated_user_is_redirected_from_index(): void
    {
        $response = $this->get(route('admin.notification.index'));

        $response->assertRedirect();
    }

    // --- Index ---

    public function test_authenticated_user_can_list_own_notifications(): void
    {
        $this->notificationFor($this->user);
        $this->notificationFor($this->user);
        $this->notificationFor($this->user);

        // Another user's notifications should not appear
        Notification::factory()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->get(route('admin.notification.index'));

        $response->assertOk();
        $response->assertViewIs('notification::index');
        $response->assertViewHas('notifications', function ($paginator) {
            return $paginator->total() === 3;
        });
    }

    public function test_index_filters_by_read_status(): void
    {
        $this->notificationFor($this->user, ['read_at' => now()]);
        $this->notificationFor($this->user, ['read_at' => null]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.notification.index', ['read' => 'true']));

        $response->assertOk();
        $response->assertViewHas('notifications', function ($paginator) {
            return $paginator->total() === 1;
        });
    }

    public function test_index_filters_by_type(): void
    {
        $this->notificationFor($this->user, ['type' => NotificationType::Info->value]);
        $this->notificationFor($this->user, ['type' => NotificationType::Export->value]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.notification.index', ['type' => 'export']));

        $response->assertOk();
        $response->assertViewHas('notifications', function ($paginator) {
            return $paginator->total() === 1;
        });
    }

    // --- Show ---

    public function test_show_returns_notification_and_marks_it_as_read(): void
    {
        $notification = $this->notificationFor($this->user, ['read_at' => null]);

        $response = $this->actingAs($this->user)
            ->get(route('admin.notification.show', $notification->id));

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_view_another_users_notification(): void
    {
        $other = User::factory()->create();
        $notification = $this->notificationFor($other);

        $response = $this->actingAs($this->user)
            ->get(route('admin.notification.show', $notification->id));

        $response->assertForbidden();
    }

    // --- Delete ---

    public function test_user_can_delete_own_notification(): void
    {
        $notification = $this->notificationFor($this->user);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.notification.destroy', $notification->id));

        $response->assertRedirect(route('admin.notification.index'));
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_delete_another_users_notification(): void
    {
        $other = User::factory()->create();
        $notification = $this->notificationFor($other);

        $response = $this->actingAs($this->user)
            ->delete(route('admin.notification.destroy', $notification->id));

        $response->assertForbidden();
    }

    // --- Mark as read ---

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $notification = $this->notificationFor($this->user, ['read_at' => null]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/notification/'.$notification->id.'/mark-as-read');

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    // --- Mark as unread ---

    public function test_user_can_mark_own_notification_as_unread(): void
    {
        $notification = $this->notificationFor($this->user, ['read_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/notification/'.$notification->id.'/mark-as-unread');

        $response->assertOk();
        $this->assertNull($notification->fresh()->read_at);
    }

    // --- Mark all as read ---

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $this->notificationFor($this->user, ['read_at' => null]);
        $this->notificationFor($this->user, ['read_at' => null]);
        $this->notificationFor($this->user, ['read_at' => null]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/notification/mark-all-as-read');

        $response->assertOk();
        $this->assertSame(
            0,
            Notification::where('notifiable_type', User::class)
                ->where('notifiable_id', $this->user->id)
                ->whereNull('read_at')
                ->count()
        );
    }

    // --- Counts (JSON via API) ---

    public function test_counts_returns_correct_total_and_unread(): void
    {
        $this->notificationFor($this->user, ['read_at' => null]);
        $this->notificationFor($this->user, ['read_at' => null]);
        $this->notificationFor($this->user, ['read_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/notification/counts');

        $response->assertOk();
        $response->assertJsonPath('data.total', 3);
        $response->assertJsonPath('data.unread', 2);
    }
}
