<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\MailerSecretCipher;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmsGatewaysTest extends TestCase
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
        Permission::create(['name' => 'Edit Special Setting', 'guard_name' => 'web', 'module_name' => 'Settings']);
        $role->givePermissionTo('Edit Special Setting');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        Setting::updateOrCreate(
            ['key' => 'sms_gateways'],
            ['type' => 'json', 'group' => 'General', 'value' => json_encode([]), 'is_visible' => false]
        );
        Setting::updateOrCreate(
            ['key' => 'sms_gateway'],
            ['type' => 'select', 'group' => 'General', 'value' => 'log', 'is_visible' => false]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function gatewayPayload(array $valueOverrides = []): array
    {
        return [
            'sms_gateway' => 'Provider',
            'sms_gateways' => [
                [
                    'TYPE' => 'Provider',
                    'VALUE' => array_merge([
                        'endpoint' => 'https://api.provider.com/send',
                        'method' => 'POST',
                        'mobile_key' => 'mobile',
                        'message_key' => 'text',
                        'params' => [
                            'keys' => ['api_key'],
                            'values' => ['secret-key'],
                        ],
                    ], $valueOverrides),
                ],
            ],
        ];
    }

    public function test_permitted_user_can_view_the_sms_gateways_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.special.sms_gateways'))
            ->assertStatus(200)
            ->assertViewIs('settings::special.sms-gateways');
    }

    public function test_unpermitted_user_cannot_update_sms_gateways(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('admin.settings.special.update_sms_gateways'), $this->gatewayPayload())
            ->assertForbidden();
    }

    public function test_it_stores_a_gateway_folding_and_encrypting_the_params(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_sms_gateways'), $this->gatewayPayload())
            ->assertSessionHas('success');

        $stored = Setting::where('key', 'sms_gateways')->first()->value;

        $this->assertSame('Provider', $stored[0]['TYPE']);
        $this->assertArrayHasKey('api_key', $stored[0]['VALUE']['params']);
        // Folded from keys/values, and encrypted at rest.
        $this->assertArrayNotHasKey('keys', $stored[0]['VALUE']['params']);
        $cipher = app(MailerSecretCipher::class);
        $this->assertTrue($cipher->isEncrypted($stored[0]['VALUE']['params']['api_key']));
        $this->assertSame('secret-key', $cipher->decrypt($stored[0]['VALUE']['params']['api_key']));
    }

    public function test_a_blank_param_value_keeps_the_stored_one(): void
    {
        $cipher = app(MailerSecretCipher::class);

        Setting::updateOrCreate(
            ['key' => 'sms_gateways'],
            ['type' => 'json', 'group' => 'General', 'is_visible' => false, 'value' => json_encode([
                ['TYPE' => 'Provider', 'VALUE' => [
                    'endpoint' => 'https://api.provider.com/send',
                    'method' => 'POST',
                    'mobile_key' => 'mobile',
                    'message_key' => 'text',
                    'params' => ['api_key' => $cipher->encrypt('stored-key')],
                ]],
            ])]
        );

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_sms_gateways'), $this->gatewayPayload([
                'params' => ['keys' => ['api_key'], 'values' => ['']],
            ]))
            ->assertSessionHas('success');

        $stored = Setting::where('key', 'sms_gateways')->first()->value;
        $this->assertSame('stored-key', $cipher->decrypt($stored[0]['VALUE']['params']['api_key']));
    }

    public function test_it_requires_the_core_gateway_fields(): void
    {
        $payload = $this->gatewayPayload();
        unset($payload['sms_gateways'][0]['VALUE']['endpoint'], $payload['sms_gateways'][0]['VALUE']['mobile_key']);

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_sms_gateways'), $payload)
            ->assertSessionHasErrors([
                'sms_gateways.0.VALUE.endpoint',
                'sms_gateways.0.VALUE.mobile_key',
            ]);
    }
}
