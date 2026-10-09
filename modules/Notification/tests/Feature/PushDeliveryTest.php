<?php

namespace Modules\Notification\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Modules\Notification\Actions\NotifyAction;
use Modules\Notification\Models\FirebaseToken;
use Modules\Notification\Models\Notification;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Push delivery end to end on the server side: which project it goes to, what the message
 * carries, dead tokens, per-device registration and the admin's audience choices.
 */
class PushDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('firebase_access_token', 'test-token');
        config(['broadcasting.default' => 'null', 'notification.project_id' => null]);
    }

    private function setting(string $key, string $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['group' => 'Firebase', 'type' => 'text', 'value' => $value, 'is_visible' => false],
        );
    }

    private function userWithToken(string $token = 'tok-1', string $device = 'dev-1'): User
    {
        $user = User::factory()->create(['is_active' => true]);
        FirebaseToken::query()->create(['user_id' => $user->id, 'token' => $token, 'device_id' => $device]);

        return $user;
    }

    public function test_the_push_goes_to_the_project_from_the_firebase_settings_with_the_in_app_id(): void
    {
        $this->setting('firebase_project_id', 'poultry-test');
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/poultry-test/messages/1'])]);
        $user = $this->userWithToken();

        NotifyAction::toUser($user, 'নতুন অর্ডার', 'body', data: ['type' => 'market_order', 'order_id' => 'o1', 'nested' => ['a' => 1]], channels: ['fcm', 'database']);

        $row = Notification::query()->sole();
        Http::assertSent(function (Request $request) use ($row): bool {
            $message = $request['message'];

            return str_contains($request->url(), '/projects/poultry-test/messages:send')
                && $message['token'] === 'tok-1'
                && $message['data']['type'] === 'market_order'
                && $message['data']['nested'] === '{"a":1}'
                && $message['data']['notification_id'] === $row->id
                && $message['android']['notification']['channel_id'] === 'general';
        });
    }

    public function test_without_a_setting_or_env_the_project_comes_from_the_service_account(): void
    {
        $this->setting('firebase_credentials_json', json_encode(['type' => 'service_account', 'project_id' => 'from-json']));
        Http::fake(['fcm.googleapis.com/*' => Http::response([])]);

        NotifyAction::toUser($this->userWithToken(), 't', 'b', channels: ['fcm']);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/projects/from-json/'));
    }

    public function test_a_token_fcm_calls_unregistered_is_forgotten(): void
    {
        $this->setting('firebase_project_id', 'p');
        Http::fake(['fcm.googleapis.com/*' => Http::response(['error' => [
            'status' => 'NOT_FOUND',
            'details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']],
        ]], 404)]);
        $user = $this->userWithToken('dead-token');

        NotifyAction::toUser($user, 't', 'b', channels: ['fcm']);

        $this->assertDatabaseMissing('firebase_tokens', ['token' => 'dead-token']);
    }

    public function test_an_empty_payload_is_returned_as_an_object(): void
    {
        $user = User::factory()->create();
        NotifyAction::toUser($user, 't', 'b', channels: ['database']);
        Sanctum::actingAs($user);

        $json = $this->getJson('/api/'.config('foundation.routing.api_prefix').'/notification')->getContent();

        $this->assertStringContainsString('"data":{}', (string) $json);
    }

    public function test_a_device_can_unregister_and_logout_keeps_other_devices(): void
    {
        $user = $this->userWithToken('phone-a', 'install-a');
        FirebaseToken::query()->create(['user_id' => $user->id, 'token' => 'phone-b', 'device_id' => 'install-b']);
        Sanctum::actingAs($user);
        $api = '/api/'.config('foundation.routing.api_prefix');

        $this->postJson("{$api}/firebase-token", ['token' => 'phone-c', 'device_id' => 'install-c', 'platform' => 'android'])->assertOk();
        $this->assertDatabaseHas('firebase_tokens', ['device_id' => 'install-c', 'platform' => 'android']);

        $this->deleteJson("{$api}/firebase-token", ['device_id' => 'install-c'])->assertOk();
        $this->assertDatabaseMissing('firebase_tokens', ['device_id' => 'install-c']);

        $user->createToken('app');
        $this->postJson("{$api}/logout", ['device_id' => 'install-a'])->assertOk();
        $this->assertDatabaseMissing('firebase_tokens', ['device_id' => 'install-a']);
        $this->assertDatabaseHas('firebase_tokens', ['device_id' => 'install-b']);
    }

    public function test_admin_sends_to_a_role_with_image_and_link(): void
    {
        $this->withoutMiddleware([PreventRequestForgery::class]);
        $this->setting('firebase_project_id', 'p');
        Http::fake(['fcm.googleapis.com/*' => Http::response([])]);
        Permission::create(['name' => 'Create Push Notification', 'guard_name' => 'web', 'module_name' => 'Notification']);
        Permission::create(['name' => 'View Push Notification', 'guard_name' => 'web', 'module_name' => 'Notification']);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->givePermissionTo('Create Push Notification', 'View Push Notification');
        $farmers = Role::create(['name' => 'farmer', 'guard_name' => 'web']);
        $farmer = $this->userWithToken('farmer-token', 'farmer-device');
        $farmer->assignRole($farmers);
        $buyer = $this->userWithToken('buyer-token', 'buyer-device');
        $inactive = $this->userWithToken('gone-token', 'gone-device');
        $inactive->assignRole($farmers);
        $inactive->forceFill(['is_active' => false])->save();

        $this->actingAs($admin)->post(route('admin.push.notification.store'), [
            'title' => 'টিকার মৌসুম',
            'body' => 'body',
            'recipient_type' => 'role',
            'recipient_role' => 'farmer',
            'url' => 'https://example.com/vaccines',
        ])->assertRedirect(route('admin.push.notification.index'));

        $this->assertSame(1, Notification::query()->where('notifiable_id', $farmer->id)->count());
        $this->assertSame(0, Notification::query()->whereIn('notifiable_id', [$buyer->id, $inactive->id])->count());
        Http::assertSent(fn (Request $r) => $r['message']['token'] === 'farmer-token'
            && $r['message']['data']['type'] === 'announcement'
            && $r['message']['data']['url'] === 'https://example.com/vaccines');
        Http::assertNotSent(fn (Request $r) => in_array($r['message']['token'], ['buyer-token', 'gone-token'], true));

        $this->get(route('admin.push.notification.index'))->assertSee('Role: farmer')->assertSee('1 recipients');
    }
}
