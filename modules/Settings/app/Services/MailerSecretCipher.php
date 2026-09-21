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
 * Every method tolerates legacy plaintext and is idempotent, so a value can be
 * passed through repeatedly (mixed plaintext/ciphertext during the migration)
 * without corruption. Ciphertext is Laravel's standard {@see Crypt} payload and
 * is therefore bound to APP_KEY.
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
     * Decrypt a stored secret. A value that is not our ciphertext (legacy
     * plaintext) is returned unchanged so consumption keeps working before the
     * migration has run.
     */
    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
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
