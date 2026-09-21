<?php

namespace Modules\Settings\Tests\Unit\Services;

use Modules\Settings\Services\MailerSecretCipher;
use Tests\TestCase;

class MailerSecretCipherTest extends TestCase
{
    private MailerSecretCipher $cipher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cipher = new MailerSecretCipher;
    }

    public function test_it_round_trips_a_secret(): void
    {
        $encrypted = $this->cipher->encrypt('super-secret');

        $this->assertNotSame('super-secret', $encrypted);
        $this->assertTrue($this->cipher->isEncrypted($encrypted));
        $this->assertSame('super-secret', $this->cipher->decrypt($encrypted));
    }

    public function test_encrypting_is_idempotent(): void
    {
        $once = $this->cipher->encrypt('super-secret');
        $twice = $this->cipher->encrypt($once);

        $this->assertSame($once, $twice);
        $this->assertSame('super-secret', $this->cipher->decrypt($twice));
    }

    public function test_decrypt_passes_legacy_plaintext_through(): void
    {
        $this->assertSame('plain-value', $this->cipher->decrypt('plain-value'));
        $this->assertFalse($this->cipher->isEncrypted('plain-value'));
    }

    public function test_null_and_empty_values_pass_through(): void
    {
        $this->assertNull($this->cipher->encrypt(null));
        $this->assertSame('', $this->cipher->encrypt(''));
        $this->assertNull($this->cipher->decrypt(null));
        $this->assertSame('', $this->cipher->decrypt(''));
    }
}
