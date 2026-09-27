<?php

declare(strict_types=1);

namespace Modules\Settings\Support;

use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Settings\Data\MailerData;
use Modules\Settings\Services\MailerSecretCipher;
use Mrj\Foundation\Contracts\SettingsRepository;
use Nwidart\Modules\Facades\Module;

/**
 * Copies the stored settings into config(): every setting under
 * `settings.{key}`, the mapped ones onto their config keys, and the active
 * mailer onto `mail.*`. It runs once at boot, and again whenever the tenant
 * changes (foundation.tenancy), because each tenant keeps its own settings.
 *
 * A setting is mapped when its definition in an enabled module's
 * config/settings.php declares `'config' => 'some.config.key'`. A stored null
 * is not applied, so the config value (and .env) stays.
 *
 * Before its first run it remembers the config values it is about to replace,
 * and puts them back before every later run, so a value one tenant set never
 * survives into the next tenant that does not set it.
 */
final class SettingsConfigApplier
{
    /** @var array<string, string>|null setting key => config key */
    private ?array $map = null;

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

                $target = $this->map()[$setting['key']] ?? null;

                if ($target !== null && $setting['value'] !== null) {
                    config([$target => $setting['value']]);
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
     * Setting key => config key, from every enabled module's settings
     * definitions (merged into config as `{alias}.settings`). Read once: the
     * definitions do not change while the process runs.
     *
     * @return array<string, string>
     */
    public function map(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $this->map = [];

        foreach (Module::allEnabled() as $module) {
            foreach ((array) config($module->getLowerName().'.settings', []) as $key => $definition) {
                if (is_string($key) && is_array($definition) && is_string($definition['config'] ?? null)) {
                    $this->map[$key] = $definition['config'];
                }
            }
        }

        return $this->map;
    }

    /**
     * The first run records what it will overwrite; every later run puts it back.
     */
    private function restore(): void
    {
        $keys = ['settings', 'mail', ...array_values($this->map())];

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
