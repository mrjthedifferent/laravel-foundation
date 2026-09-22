<?php

namespace Modules\Settings\Tests\Unit\Data;

use Modules\Settings\Data\SmsGatewayData;
use Modules\Settings\Services\MailerSecretCipher;
use Tests\TestCase;

class SmsGatewayDataTest extends TestCase
{
    private MailerSecretCipher $cipher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cipher = new MailerSecretCipher;
    }

    public function test_it_folds_keys_values_arrays_and_encrypts_the_values(): void
    {
        $entry = SmsGatewayData::fromEntry([
            'TYPE' => 'Provider',
            'VALUE' => [
                'endpoint' => 'https://api.provider.com/send',
                'method' => 'post',
                'mobile_key' => 'mobile',
                'message_key' => 'text',
                'params' => [
                    'keys' => ['api_key', 'sender'],
                    'values' => ['secret-key', 'Acme'],
                ],
            ],
        ])->toEntry($this->cipher);

        $this->assertSame('POST', $entry['VALUE']['method']);
        $this->assertArrayHasKey('api_key', $entry['VALUE']['params']);
        // Stored encrypted, not plaintext.
        $this->assertNotSame('secret-key', $entry['VALUE']['params']['api_key']);
        $this->assertTrue($this->cipher->isEncrypted($entry['VALUE']['params']['api_key']));

        // And decrypts back for consumption.
        $decrypted = SmsGatewayData::decryptValue($entry['VALUE'], $this->cipher);
        $this->assertSame('secret-key', $decrypted['params']['api_key']);
        $this->assertSame('Acme', $decrypted['params']['sender']);
    }

    public function test_a_blank_value_keeps_the_stored_ciphertext_for_that_key(): void
    {
        $storedValue = [
            'params' => ['api_key' => $this->cipher->encrypt('stored-key')],
        ];

        $entry = SmsGatewayData::fromEntry([
            'TYPE' => 'Provider',
            'VALUE' => [
                'endpoint' => 'https://api.provider.com/send',
                'method' => 'POST',
                'mobile_key' => 'mobile',
                'message_key' => 'text',
                'params' => [
                    'keys' => ['api_key'],
                    'values' => [''],
                ],
            ],
        ])->toEntry($this->cipher, $storedValue);

        $this->assertSame('stored-key', $this->cipher->decrypt($entry['VALUE']['params']['api_key']));
    }
}
