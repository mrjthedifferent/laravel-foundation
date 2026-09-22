<?php

namespace Modules\Settings\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Modules\Settings\Contracts\SecretCipher;

/**
 * Encrypts and decrypts secret values held in the settings table: the leaf
 * values inside the email_mailers and sms_gateways JSON settings (mailbox
 * passwords, client secrets, SMS API keys), and any setting stored with
 * type "encrypted" (webhook URLs, bot tokens, service-account JSON).
 *
 * Encryption is idempotent, so re-saving an already-encrypted value never
 * double-encrypts. Ciphertext is Laravel's standard {@see Crypt} payload and is
 * therefore bound to APP_KEY.
 */
final readonly class MailerSecretCipher implements SecretCipher
{
    /**
     * Encrypt a plaintext secret. Null/empty values pass through untouched (a
     * blank field means "no secret"), and an already-encrypted value is returned
     * as-is so re-saving never double-encrypts.
     */
    public function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if ($this->isEncrypted($value)) {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    /**
     * Decrypt a stored secret. Null/empty values pass through untouched. A value
     * that is not valid ciphertext (tampered, or encrypted under a different
     * APP_KEY) yields null, so a single unreadable secret reads as "not set"
     * instead of breaking the settings read on every request.
     */
    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Whether the value is already a Crypt payload rather than plaintext.
     */
    public function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
