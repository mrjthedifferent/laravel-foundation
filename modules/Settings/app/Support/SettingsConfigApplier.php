<?php

declare(strict_types=1);

namespace Modules\Settings\Support;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Data\MailerData;
use Modules\Settings\Services\MailerSecretCipher;
use Mrj\Foundation\Contracts\SettingsRepository;

/**
 * Copies the stored settings into config(): every setting under
 * `settings.{key}`, the mapped ones onto their framework keys, and the active
 * mailer onto `mail.*`. It runs once at boot, and again whenever the tenant
 * changes (foundation.tenancy), because each tenant keeps its own settings.
 *
 * Before its first run it remembers the config values it is about to replace,
 * and puts them back before every later run, so a value one tenant set never
 * survives into the next tenant that does not set it.
 */
final class SettingsConfigApplier
{
    /** @var array<string, string> setting key => config key */
    public const array CONFIG_MAP = [
        'google_client_id' => 'services.google.client_id',
        'google_client_secret' => 'services.google.client_secret',
        'google_redirect_uri' => 'services.google.redirect',
        'github_client_id' => 'services.github.client_id',
        'github_client_secret' => 'services.github.client_secret',
        'github_redirect_uri' => 'services.github.redirect',
        'apple_client_id' => 'services.apple.client_id',
        'apple_client_secret' => 'services.apple.client_secret',
        'apple_redirect_uri' => 'services.apple.redirect',
        'apple_team_id' => 'services.apple.team_id',
        'apple_key_id' => 'services.apple.key_id',
        'apple_key_file' => 'services.apple.key_file',
        'error_report_slack_webhook' => 'logging.channels.slack.url',
    ];

    /** @var array<string, mixed>|null config key => value before the first run */
    private ?array $snapshot = null;

    public function __construct(private readonly SettingsRepository $settings) {}

    /**
     * Relies on the try/catch below rather than a Schema::hasTable() probe
     * (a query on every single request otherwise): a fresh install's very
     * first boot, before migrations run, simply finds no settings and hits
     * this catch, exactly as it would for any other database error.
     */
    public function apply(): void
    {
        $this->restore();

        try {
            $settings = $this->settings->all();

            $activeMailer = collect($settings)->firstWhere('key', 'email_mailer')['value'] ?? null;

            foreach ($settings as $setting) {
                if ($setting['key'] === 'email_mailers') {
                    $this->applyMailers($setting, $activeMailer);

                    continue;
                }

                if (isset(self::CONFIG_MAP[$setting['key']])) {
                    config([self::CONFIG_MAP[$setting['key']] => $setting['value']]);
                }

                config([
                    'settings.'.$setting['key'] => [
                        'group' => $setting['group'],
                        'type' => $setting['type'],
                        'value' => $setting['value'],
                        'description' => $setting['description'],
                    ],
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error updating configs from settings: '.$e->getMessage());
        }
    }

    /**
     * The first run records what it will overwrite; every later run puts it back.
     */
    private function restore(): void
    {
        $keys = ['settings', 'mail', ...array_values(self::CONFIG_MAP)];

        if ($this->snapshot === null) {
            $this->snapshot = [];

            foreach ($keys as $key) {
                $this->snapshot[$key] = config($key);
            }

            return;
        }

        config($this->snapshot);
    }

    /**
     * @param  array{key: string, value: mixed}  $setting
     */
    private function applyMailers(array $setting, ?string $activeMailer): void
    {
        try {
            if (! $activeMailer) {
                return;
            }

            foreach ((array) $setting['value'] as $mailer) {
                if ($mailer['TYPE'] === $activeMailer) {
                    $transport = $mailer['VALUE']['transport'];
                    config(['mail.default' => $transport]);

                    // Secrets are stored encrypted; decrypt them before they reach
                    // the transport config.
                    $value = MailerData::decryptValue($mailer['VALUE'], new MailerSecretCipher);

                    foreach ($value as $key => $val) {
                        if (empty($transport)) {
                            continue;
                        }
                        if ($key === 'from') {
                            config(['mail.from' => $val]);

                            continue;
                        }
                        config(['mail.mailers.'.$transport.'.'.$key => $val]);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('Error updating configs from settings: '.$e->getMessage());
        }
    }
}
