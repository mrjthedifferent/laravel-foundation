<?php

namespace Modules\Settings\Tests\Unit\Data;

use Modules\Settings\Data\MailerData;
use Modules\Settings\Services\MailerSecretCipher;
use Tests\TestCase;

class MailerDataTest extends TestCase
{
    private MailerSecretCipher $cipher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cipher = new MailerSecretCipher;
    }

    public function test_it_prunes_a_log_mailer_to_transport_and_from_only(): void
    {
        $entry = MailerData::fromEntry([
            'TYPE' => 'log',
            'VALUE' => [
                'transport' => 'log',
                'host' => 'localhost',
                'port' => '0000',
                'tenant_id' => null,
                'from' => ['address' => 'noreply@example.com', 'name' => 'Platform'],
            ],
        ])->toEntry($this->cipher);

        $this->assertSame(['transport', 'from'], array_keys($entry['VALUE']));
        $this->assertArrayNotHasKey('host', $entry['VALUE']);
        $this->assertArrayNotHasKey('tenant_id', $entry['VALUE']);
    }

    public function test_it_prunes_a_graph_mailer_to_its_own_fields(): void
    {
        $entry = MailerData::fromEntry([
            'TYPE' => 'Graph',
            'VALUE' => [
                'transport' => 'microsoft_graph',
                'host' => 'smtp.office365.com',
                'port' => '587',
                'tenant_id' => 'tenant-uuid',
                'client_id' => 'client-uuid',
                'client_secret' => 'super-secret',
                'mailbox' => 'mail@company.com',
                'from' => ['address' => 'mail@company.com', 'name' => 'Company'],
            ],
        ])->toEntry($this->cipher);

        $this->assertEqualsCanonicalizing(
            ['transport', 'tenant_id', 'client_id', 'client_secret', 'mailbox', 'from'],
            array_keys($entry['VALUE'])
        );
        $this->assertArrayNotHasKey('host', $entry['VALUE']);
    }

    public function test_it_encrypts_the_secret_and_can_decrypt_it_back(): void
    {
        $entry = MailerData::fromEntry([
            'TYPE' => 'Graph',
            'VALUE' => [
                'transport' => 'microsoft_graph',
                'tenant_id' => 'tenant-uuid',
                'client_id' => 'client-uuid',
                'client_secret' => 'super-secret',
                'mailbox' => 'mail@company.com',
                'from' => ['address' => 'mail@company.com', 'name' => 'Company'],
            ],
        ])->toEntry($this->cipher);

        $this->assertNotSame('super-secret', $entry['VALUE']['client_secret']);
        $this->assertTrue($this->cipher->isEncrypted($entry['VALUE']['client_secret']));

        $decrypted = MailerData::decryptValue($entry['VALUE'], $this->cipher);
        $this->assertSame('super-secret', $decrypted['client_secret']);
    }

    public function test_a_blank_secret_keeps_the_stored_ciphertext(): void
    {
        $storedValue = [
            'transport' => 'microsoft_graph',
            'client_secret' => $this->cipher->encrypt('stored-secret'),
        ];

        $entry = MailerData::fromEntry([
            'TYPE' => 'Graph',
            'VALUE' => [
                'transport' => 'microsoft_graph',
                'tenant_id' => 'tenant-uuid',
                'client_id' => 'client-uuid',
                'client_secret' => '',
                'mailbox' => 'mail@company.com',
                'from' => ['address' => 'mail@company.com', 'name' => 'Company'],
            ],
        ])->toEntry($this->cipher, $storedValue);

        $this->assertSame('stored-secret', $this->cipher->decrypt($entry['VALUE']['client_secret']));
    }
}
