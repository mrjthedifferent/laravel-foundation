<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Modules\Settings\Data\MailerData;
use Modules\Settings\Data\SmsGatewayData;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\MailerSecretCipher;

/**
 * Normalizes the email_mailers and sms_gateways JSON settings to the pruned,
 * per-transport shape and encrypts their secret values at rest.
 *
 * Idempotent: an already-pruned entry re-prunes to itself and an already-
 * encrypted secret is left untouched, so re-running is safe.
 *
 * The ciphertext is bound to APP_KEY — rotating the key makes the stored secrets
 * unreadable and they must be re-entered in Settings > Email Mailers / SMS
 * Gateways. Historical audit rows still hold the pre-migration plaintext; scrub
 * them and rotate the affected secrets separately.
 */
return new class extends Migration
{
    public function up(): void
    {
        $cipher = new MailerSecretCipher;

        $this->transform('email_mailers', fn (array $entry) => MailerData::fromEntry($entry)->toEntry($cipher));
        $this->transform('sms_gateways', fn (array $entry) => SmsGatewayData::fromEntry($entry)->toEntry($cipher));

        Cache::forget('app_settings');
    }

    public function down(): void
    {
        $cipher = new MailerSecretCipher;

        // Best effort: decrypt secrets back to plaintext. Fields pruned on the way
        // up are not restored — the transports do not need them.
        $this->transform('email_mailers', function (array $entry) use ($cipher) {
            if (is_array($entry['VALUE'] ?? null)) {
                $entry['VALUE'] = MailerData::decryptValue($entry['VALUE'], $cipher);
            }

            return $entry;
        });
        $this->transform('sms_gateways', function (array $entry) use ($cipher) {
            if (is_array($entry['VALUE'] ?? null)) {
                $entry['VALUE'] = SmsGatewayData::decryptValue($entry['VALUE'], $cipher);
            }

            return $entry;
        });

        Cache::forget('app_settings');
    }

    /**
     * Map every entry of a JSON settings row through $mapper and save it back.
     */
    private function transform(string $key, callable $mapper): void
    {
        $setting = Setting::where('key', $key)->first();

        if (! $setting || ! is_array($setting->value)) {
            return;
        }

        $setting->value = array_values(array_map(
            fn ($entry) => is_array($entry) ? $mapper($entry) : $entry,
            $setting->value,
        ));
        $setting->save();
    }
};
