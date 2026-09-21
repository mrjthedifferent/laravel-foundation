<?php

namespace Modules\Settings\Contracts;

/**
 * Encrypts and decrypts secret values held in the settings table (API keys,
 * webhook URLs, service-account JSON, mailbox passwords) so they are never
 * stored, backed up or audited in plaintext.
 */
interface SecretCipher
{
    /**
     * Encrypt a plaintext secret. Null/empty values pass through untouched (a
     * blank field means "no secret"), and an already-encrypted value is
     * returned as-is so re-saving never double-encrypts.
     */
    public function encrypt(?string $value): ?string;

    /**
     * Decrypt a stored secret. A value that is not our ciphertext (legacy
     * plaintext) is returned unchanged so consumption keeps working before a
     * migration has run.
     */
    public function decrypt(?string $value): ?string;

    /**
     * Whether the value is already a ciphertext payload rather than plaintext.
     */
    public function isEncrypted(string $value): bool;
}
