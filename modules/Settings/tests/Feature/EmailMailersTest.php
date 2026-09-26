<?php

namespace Modules\Settings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Settings\Database\Seeders\SettingsSettingsSeeder;
use Modules\Settings\Mail\Transport\MicrosoftGraphTransport;
use Modules\Settings\Mail\Transport\MicrosoftOAuthTransport;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\MailerSecretCipher;
use Modules\Settings\Services\MicrosoftOAuthTokenService;
use Modules\Settings\Support\SettingsConfigApplier;
use Mrj\Foundation\Contracts\SettingsRepository;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailMailersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PreventRequestForgery::class,
        ]);

        $role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Permission::create(['name' => 'Edit Special Setting', 'guard_name' => 'web', 'module_name' => 'Settings']);
        $role->givePermissionTo('Edit Special Setting');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        Setting::updateOrCreate(
            ['key' => 'email_mailers'],
            ['type' => 'json', 'group' => 'General', 'value' => json_encode([]), 'is_visible' => false]
        );
        Setting::updateOrCreate(
            ['key' => 'email_mailer'],
            ['type' => 'select', 'group' => 'General', 'value' => 'log', 'is_visible' => false]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function outlookPayload(array $overrides = []): array
    {
        return [
            'email_mailer' => 'Outlook365',
            'email_mailers' => [
                [
                    'TYPE' => 'Outlook365',
                    'VALUE' => array_merge([
                        'transport' => 'microsoft_oauth',
                        'tenant_id' => 'tenant-uuid',
                        'client_id' => 'client-uuid',
                        'client_secret' => 'super-secret',
                        'mailbox' => 'mail@company.com',
                        'from' => ['address' => 'mail@company.com', 'name' => 'Company'],
                    ], $overrides),
                ],
            ],
        ];
    }

    public function test_permitted_user_can_view_the_email_mailers_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.special.email_mailers'));

        $response->assertStatus(200);
        $response->assertViewIs('settings::special.email-mailers');
        $response->assertViewHas('emailMailers');
    }

    /**
     * The empty-mailers fixture in setUp() never reaches the per-mailer
     * fields — render the page against one smtp and one microsoft_oauth
     * mailer (the two branches with distinct fields) to exercise them.
     */
    public function test_page_renders_configured_smtp_and_oauth_mailers(): void
    {
        $cipher = new MailerSecretCipher;

        Setting::updateOrCreate(
            ['key' => 'email_mailers'],
            ['type' => 'json', 'group' => 'General', 'is_visible' => false, 'value' => json_encode([
                ['TYPE' => 'Gmail', 'VALUE' => [
                    'transport' => 'smtp',
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => 'me@gmail.com',
                    'password' => $cipher->encrypt('app-password'),
                    'from' => ['address' => 'me@gmail.com', 'name' => 'Me'],
                ]],
                ['TYPE' => 'Outlook365', 'VALUE' => [
                    'transport' => 'microsoft_oauth',
                    'tenant_id' => 'tenant-123',
                    'client_id' => 'client-123',
                    'client_secret' => $cipher->encrypt('super-secret'),
                    'mailbox' => 'mail@company.com',
                    'from' => ['address' => 'mail@company.com', 'name' => 'Company'],
                ]],
            ])]
        );

        $response = $this->actingAs($this->admin)->get(route('admin.settings.special.email_mailers'));

        $response->assertStatus(200);
        $response->assertSee('smtp.gmail.com');
        $response->assertSee('tenant-123');
        $response->assertSee('•••••••• (unchanged)');
    }

    public function test_unpermitted_user_cannot_view_the_email_mailers_page(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->get(route('admin.settings.special.email_mailers'))
            ->assertForbidden();
    }

    public function test_unpermitted_user_cannot_update_email_mailers(): void
    {
        $this->actingAs(User::factory()->create(['is_active' => true]))
            ->post(route('admin.settings.special.update_email_mailers'), $this->outlookPayload())
            ->assertForbidden();
    }

    public function test_it_stores_a_microsoft_oauth_mailer(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $this->outlookPayload());

        $response->assertSessionHas('success');

        $stored = Setting::where('key', 'email_mailers')->first()->value;

        $this->assertSame('Outlook365', $stored[0]['TYPE']);
        $this->assertSame('microsoft_oauth', $stored[0]['VALUE']['transport']);
        $this->assertSame('tenant-uuid', $stored[0]['VALUE']['tenant_id']);
        $this->assertSame('mail@company.com', $stored[0]['VALUE']['mailbox']);
        $this->assertSame('Outlook365', Setting::where('key', 'email_mailer')->first()->value);
    }

    public function test_it_still_stores_a_plain_smtp_mailer(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.special.update_email_mailers'), [
            'email_mailer' => 'Gmail',
            'email_mailers' => [
                [
                    'TYPE' => 'Gmail',
                    'VALUE' => [
                        'transport' => 'smtp',
                        'host' => 'smtp.gmail.com',
                        'port' => 587,
                        'encryption' => 'tls',
                        'username' => 'me@gmail.com',
                        'password' => 'app-password',
                        'from' => ['address' => 'me@gmail.com', 'name' => 'Me'],
                    ],
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $this->assertSame('smtp.gmail.com', Setting::where('key', 'email_mailers')->first()->value[0]['VALUE']['host']);
    }

    /**
     * The seeded "log" mailer carries port "0000", which fails both the integer
     * and between rules. Its presence must not block saving another mailer.
     */
    public function test_it_stores_an_oauth_mailer_alongside_the_seeded_log_mailer(): void
    {
        $payload = $this->outlookPayload();
        array_unshift($payload['email_mailers'], [
            'TYPE' => 'log',
            'VALUE' => [
                'transport' => 'log',
                'host' => 'localhost',
                'port' => '0000',
                'encryption' => 'tls',
                'username' => null,
                'password' => null,
                'from' => ['address' => 'noreply@example.com', 'name' => 'Platform'],
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $stored = Setting::where('key', 'email_mailers')->first()->value;

        $this->assertCount(2, $stored);

        // Submitted order must survive the round trip. The log mailer is pruned to
        // just its transport + from — the irrelevant host/port are dropped.
        $this->assertSame('log', $stored[0]['TYPE']);
        $this->assertSame('log', $stored[0]['VALUE']['transport']);
        $this->assertArrayNotHasKey('host', $stored[0]['VALUE']);
        $this->assertArrayNotHasKey('port', $stored[0]['VALUE']);
        $this->assertSame('Outlook365', $stored[1]['TYPE']);
        $this->assertSame('microsoft_oauth', $stored[1]['VALUE']['transport']);
    }

    public function test_it_requires_the_oauth_credentials_for_a_microsoft_oauth_mailer(): void
    {
        $payload = $this->outlookPayload();
        unset($payload['email_mailers'][0]['VALUE']['tenant_id'], $payload['email_mailers'][0]['VALUE']['client_id']);

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $payload)
            ->assertSessionHasErrors([
                'email_mailers.0.VALUE.tenant_id',
                'email_mailers.0.VALUE.client_id',
            ]);
    }

    public function test_a_blank_client_secret_keeps_the_stored_one(): void
    {
        // Seed a stored mailer that already has a secret.
        Setting::updateOrCreate(
            ['key' => 'email_mailers'],
            ['type' => 'json', 'group' => 'General', 'is_visible' => false, 'value' => json_encode([
                ['TYPE' => 'Outlook365', 'VALUE' => [
                    'transport' => 'microsoft_oauth',
                    'tenant_id' => 'tenant-uuid',
                    'client_id' => 'client-uuid',
                    'client_secret' => 'stored-secret',
                    'mailbox' => 'mail@company.com',
                    'from' => ['address' => 'mail@company.com', 'name' => 'Company'],
                ]],
            ])]
        );

        // Resubmit the same mailer with an empty secret (the form never renders it back).
        $payload = $this->outlookPayload(['client_secret' => '']);

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $payload)
            ->assertSessionHas('success');

        $stored = Setting::where('key', 'email_mailers')->first()->value;
        $this->assertSame('stored-secret', app(MailerSecretCipher::class)->decrypt($stored[0]['VALUE']['client_secret']));
    }

    public function test_a_blank_smtp_password_keeps_the_stored_one(): void
    {
        Setting::updateOrCreate(
            ['key' => 'email_mailers'],
            ['type' => 'json', 'group' => 'General', 'is_visible' => false, 'value' => json_encode([
                ['TYPE' => 'Gmail', 'VALUE' => [
                    'transport' => 'smtp',
                    'host' => 'smtp.gmail.com',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => 'me@gmail.com',
                    'password' => 'stored-app-password',
                    'from' => ['address' => 'me@gmail.com', 'name' => 'Me'],
                ]],
            ])]
        );

        $this->actingAs($this->admin)->post(route('admin.settings.special.update_email_mailers'), [
            'email_mailer' => 'Gmail',
            'email_mailers' => [
                [
                    'TYPE' => 'Gmail',
                    'VALUE' => [
                        'transport' => 'smtp',
                        'host' => 'smtp.gmail.com',
                        'port' => 587,
                        'encryption' => 'tls',
                        'username' => 'me@gmail.com',
                        'password' => '',
                        'from' => ['address' => 'me@gmail.com', 'name' => 'Me'],
                    ],
                ],
            ],
        ])->assertSessionHas('success');

        $stored = Setting::where('key', 'email_mailers')->first()->value;
        $this->assertSame('stored-app-password', app(MailerSecretCipher::class)->decrypt($stored[0]['VALUE']['password']));
    }

    public function test_a_new_client_secret_overrides_the_stored_one(): void
    {
        Setting::updateOrCreate(
            ['key' => 'email_mailers'],
            ['type' => 'json', 'group' => 'General', 'is_visible' => false, 'value' => json_encode([
                ['TYPE' => 'Outlook365', 'VALUE' => [
                    'transport' => 'microsoft_oauth',
                    'tenant_id' => 'tenant-uuid',
                    'client_id' => 'client-uuid',
                    'client_secret' => 'stored-secret',
                    'mailbox' => 'mail@company.com',
                    'from' => ['address' => 'mail@company.com', 'name' => 'Company'],
                ]],
            ])]
        );

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $this->outlookPayload(['client_secret' => 'rotated-secret']))
            ->assertSessionHas('success');

        $stored = Setting::where('key', 'email_mailers')->first()->value;
        $secret = $stored[0]['VALUE']['client_secret'];
        $this->assertTrue(app(MailerSecretCipher::class)->isEncrypted($secret));
        $this->assertSame('rotated-secret', app(MailerSecretCipher::class)->decrypt($secret));
    }

    public function test_it_requires_host_for_an_smtp_mailer(): void
    {
        $this->actingAs($this->admin)->post(route('admin.settings.special.update_email_mailers'), [
            'email_mailer' => 'Gmail',
            'email_mailers' => [
                [
                    'TYPE' => 'Gmail',
                    'VALUE' => [
                        'transport' => 'smtp',
                        'from' => ['address' => 'me@gmail.com', 'name' => 'Me'],
                    ],
                ],
            ],
        ])->assertSessionHasErrors(['email_mailers.0.VALUE.host', 'email_mailers.0.VALUE.port']);
    }

    public function test_it_rejects_an_unknown_transport(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $this->outlookPayload(['transport' => 'carrier-pigeon']))
            ->assertSessionHasErrors('email_mailers.0.VALUE.transport');
    }

    public function test_it_rejects_an_active_mailer_that_is_not_being_saved(): void
    {
        $payload = $this->outlookPayload();
        $payload['email_mailer'] = 'DoesNotExist';

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $payload)
            ->assertSessionHasErrors('email_mailer');
    }

    public function test_saving_oauth_credentials_clears_the_cached_token(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'token-abc', 'expires_in' => 3599]),
        ]);

        app(MicrosoftOAuthTokenService::class)
            ->accessToken('tenant-uuid', 'client-uuid', 'old-secret');

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $this->outlookPayload())
            ->assertSessionHas('success');

        $this->assertNull(Cache::get('microsoft_smtp_token:'.sha1('tenant-uuid|client-uuid')));
    }

    public function test_the_microsoft_oauth_mailer_resolves_to_the_oauth_transport(): void
    {
        config([
            'mail.mailers.microsoft_oauth' => [
                'transport' => 'microsoft_oauth',
                'host' => 'smtp.office365.com',
                'port' => 587,
                'tenant_id' => 'tenant-uuid',
                'client_id' => 'client-uuid',
                'client_secret' => 'super-secret',
                'mailbox' => 'mail@company.com',
            ],
        ]);

        $transport = Mail::mailer('microsoft_oauth')->getSymfonyTransport();

        $this->assertInstanceOf(MicrosoftOAuthTransport::class, $transport);
        $this->assertSame('mail@company.com', $transport->getUsername());
        $this->assertStringNotContainsString('super-secret', (string) $transport);
    }

    public function test_it_stores_a_microsoft_graph_mailer(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $this->outlookPayload(['transport' => 'microsoft_graph']));

        $response->assertSessionHas('success');

        $stored = Setting::where('key', 'email_mailers')->first()->value;

        $this->assertSame('microsoft_graph', $stored[0]['VALUE']['transport']);
        $this->assertSame('mail@company.com', $stored[0]['VALUE']['mailbox']);
        // Pruned to the graph transport's fields, and the secret is encrypted at rest.
        $this->assertArrayNotHasKey('host', $stored[0]['VALUE']);
        $this->assertArrayNotHasKey('username', $stored[0]['VALUE']);
        $this->assertTrue(app(MailerSecretCipher::class)->isEncrypted($stored[0]['VALUE']['client_secret']));
    }

    public function test_it_requires_the_credentials_for_a_microsoft_graph_mailer(): void
    {
        $payload = $this->outlookPayload(['transport' => 'microsoft_graph']);
        unset($payload['email_mailers'][0]['VALUE']['client_id'], $payload['email_mailers'][0]['VALUE']['mailbox']);

        $this->actingAs($this->admin)
            ->post(route('admin.settings.special.update_email_mailers'), $payload)
            ->assertSessionHasErrors([
                'email_mailers.0.VALUE.client_id',
                'email_mailers.0.VALUE.mailbox',
            ]);
    }

    public function test_the_microsoft_graph_mailer_resolves_to_the_graph_transport(): void
    {
        config([
            'mail.mailers.microsoft_graph' => [
                'transport' => 'microsoft_graph',
                'tenant_id' => 'tenant-uuid',
                'client_id' => 'client-uuid',
                'client_secret' => 'super-secret',
                'mailbox' => 'mail@company.com',
            ],
        ]);

        $transport = Mail::mailer('microsoft_graph')->getSymfonyTransport();

        $this->assertInstanceOf(MicrosoftGraphTransport::class, $transport);
        $this->assertSame('microsoft-graph://mail@company.com', (string) $transport);
        $this->assertStringNotContainsString('super-secret', (string) $transport);
    }

    /**
     * The custom transports are registered through Mail::extend closures, which
     * resolve a service out of the container. Nothing else builds them, so
     * without this they only fail when someone actually sends mail.
     */
    public function test_the_oauth_transport_is_built_from_the_stored_mailer_config(): void
    {
        config([
            'mail.default' => 'microsoft_oauth',
            'mail.mailers.microsoft_oauth' => [
                'transport' => 'microsoft_oauth',
                'tenant_id' => 'tenant-uuid',
                'client_id' => 'client-uuid',
                'client_secret' => 'super-secret',
                'mailbox' => 'mail@company.com',
            ],
        ]);

        $transport = Mail::mailer('microsoft_oauth')->getSymfonyTransport();

        $this->assertInstanceOf(MicrosoftOAuthTransport::class, $transport);
        $this->assertSame('mail@company.com', $transport->getUsername());
    }

    public function test_the_graph_transport_posts_the_mime_message_to_graph_with_a_bearer_token(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'graph-token', 'expires_in' => 3599]),
            'https://graph.microsoft.com/*' => Http::response([], 202),
        ]);

        config([
            'mail.default' => 'microsoft_graph',
            'mail.mailers.microsoft_graph' => [
                'transport' => 'microsoft_graph',
                'tenant_id' => 'tenant-uuid',
                'client_id' => 'client-uuid',
                'client_secret' => 'super-secret',
                'mailbox' => 'mail@company.com',
            ],
        ]);

        Mail::raw('Hello body', function ($message): void {
            $message->to('someone@example.com')->subject('Graph Hello')->from('mail@company.com', 'Company');
        });

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://graph.microsoft.com/v1.0/users/mail%40company.com/sendMail') {
                return false;
            }

            $mime = base64_decode((string) $request->body(), true);

            return $mime !== false
                && $request->hasHeader('Authorization', 'Bearer graph-token')
                && str_contains($mime, 'Subject: Graph Hello')
                && str_contains($mime, 'someone@example.com');
        });
    }

    public function test_a_fresh_install_sends_with_the_env_mailer(): void
    {
        Setting::query()->whereIn('key', ['email_mailer', 'email_mailers'])->delete();
        $this->seed(SettingsSettingsSeeder::class);
        $before = config('mail.default');

        app(SettingsRepository::class)->forget();
        app(SettingsConfigApplier::class)->apply();

        $this->assertSame('', Setting::query()->where('key', 'email_mailer')->value('value') ?? '');
        $this->assertSame($before, config('mail.default'));
    }
}
