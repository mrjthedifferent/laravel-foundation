<?php

namespace Modules\Settings\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\MailerSecretCipher;
use Tests\TestCase;

class NormalizeMailerSettingsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require module_path('Settings', 'database/migrations/2026_07_30_000000_normalize_and_encrypt_mailer_settings.php');
    }

    public function test_it_prunes_and_encrypts_legacy_rows_and_is_idempotent(): void
    {
        $cipher = app(MailerSecretCipher::class);

        // Legacy bloated + plaintext email mailer (union of all transport fields).
        Setting::updateOrCreate(
            ['key' => 'email_mailers'],
            ['type' => 'json', 'group' => 'General', 'is_visible' => false, 'value' => json_encode([
                ['TYPE' => 'Graph', 'VALUE' => [
                    'transport' => 'microsoft_graph',
                    'host' => 'smtp.office365.com',
                    'port' => '587',
                    'encryption' => 'tls',
                    'username' => null,
                    'password' => null,
                    'tenant_id' => 'tenant-uuid',
                    'client_id' => 'client-uuid',
                    'client_secret' => 'plain-secret',
                    'mailbox' => 'mail@company.com',
                    'from' => ['address' => 'mail@company.com', 'name' => 'Company'],
                ]],
            ])]
        );

        // Legacy plaintext SMS gateway.
        Setting::updateOrCreate(
            ['key' => 'sms_gateways'],
            ['type' => 'json', 'group' => 'General', 'is_visible' => false, 'value' => json_encode([
                ['TYPE' => 'Provider', 'VALUE' => [
                    'endpoint' => 'https://api.provider.com/send',
                    'method' => 'POST',
                    'mobile_key' => 'mobile',
                    'message_key' => 'text',
                    'params' => ['api_key' => 'plain-key'],
                ]],
            ])]
        );

        $this->migration()->up();

        $mailer = Setting::where('key', 'email_mailers')->first()->value[0];
        $this->assertArrayNotHasKey('host', $mailer['VALUE']);
        $this->assertArrayNotHasKey('username', $mailer['VALUE']);
        $this->assertTrue($cipher->isEncrypted($mailer['VALUE']['client_secret']));
        $this->assertSame('plain-secret', $cipher->decrypt($mailer['VALUE']['client_secret']));

        $gateway = Setting::where('key', 'sms_gateways')->first()->value[0];
        $this->assertTrue($cipher->isEncrypted($gateway['VALUE']['params']['api_key']));
        $this->assertSame('plain-key', $cipher->decrypt($gateway['VALUE']['params']['api_key']));

        // Second run must not double-encrypt or change the payload.
        $before = Setting::where('key', 'email_mailers')->first()->value;
        $this->migration()->up();
        $after = Setting::where('key', 'email_mailers')->first()->value;

        $this->assertSame($before, $after);
        $this->assertSame('plain-secret', $cipher->decrypt($after[0]['VALUE']['client_secret']));
    }
}
