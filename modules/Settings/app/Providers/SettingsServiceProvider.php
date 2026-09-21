<?php

namespace Modules\Settings\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Modules\Settings\Data\MailerData;
use Modules\Settings\Mail\Transport\MicrosoftGraphTransport;
use Modules\Settings\Mail\Transport\MicrosoftOAuthTransport;
use Modules\Settings\Models\Setting;
use Modules\Settings\Policies\SettingPolicy;
use Modules\Settings\Services\MailerSecretCipher;
use Modules\Settings\Services\MicrosoftOAuthTokenService;
use Modules\Settings\View\Composers\SettingsWidgetComposer;
use Mrj\Foundation\Support\ModuleServiceProvider;

class SettingsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Settings';

    protected string $nameLower = 'settings';

    protected array $morphMap = [
        'setting' => Setting::class,
    ];

    protected array $policies = [
        Setting::class => SettingPolicy::class,
    ];

    protected array $composers = [
        'settings::partials.dashboard-widget' => SettingsWidgetComposer::class,
    ];

    protected array $configMap = [
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

    public function boot(): void
    {
        parent::boot();

        $this->registerMicrosoftOAuthMailer();
        $this->registerMicrosoftGraphMailer();
        $this->updateConfigsFromSettings();
    }

    /**
     * Register the "microsoft_oauth" mail transport, which authenticates to
     * Exchange Online with XOAUTH2 instead of a mailbox password.
     *
     * The credentials arrive in $config because updateMailers() copies every
     * key of the active mailer's VALUE into mail.mailers.{transport}.*.
     */
    private function registerMicrosoftOAuthMailer(): void
    {
        // Laravel 13 binds driver closures to the mail manager, so $this is not
        // this provider here; the container is captured explicitly.
        $app = $this->app;

        Mail::extend('microsoft_oauth', fn (array $config) => new MicrosoftOAuthTransport(
            $app->make(MicrosoftOAuthTokenService::class),
            $config,
        ));
    }

    /**
     * Register the "microsoft_graph" mail transport, which sends through the
     * Microsoft Graph sendMail API. Unlike SMTP it is unaffected by Security
     * Defaults blocking basic auth and needs no Exchange service principal.
     */
    private function registerMicrosoftGraphMailer(): void
    {
        $app = $this->app;

        Mail::extend('microsoft_graph', fn (array $config) => new MicrosoftGraphTransport(
            $app->make(MicrosoftOAuthTokenService::class),
            $config,
        ));
    }

    private function updateConfigsFromSettings(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                $settings = self::cached();

                $activeMailer = collect($settings)->firstWhere('key', 'email_mailer')['value'] ?? null;

                foreach ($settings as $setting) {
                    if ($setting['key'] === 'email_mailers') {
                        $this->updateMailers($setting, $activeMailer);

                        continue;
                    }

                    if (isset($this->configMap[$setting['key']])) {
                        config([$this->configMap[$setting['key']] => $setting['value']]);
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
            }
        } catch (\Exception $e) {
            Log::error('Error updating configs from settings: '.$e->getMessage());
        }
    }

    /**
     * Every setting as a plain array, cached.
     *
     * Deliberately not an Eloquent collection: Laravel 13 refuses to unserialize
     * cached PHP objects unless they are allow-listed in cache.serializable_classes.
     *
     * @return list<array{key: string, value: mixed, group: ?string, type: ?string, description: ?string}>
     */
    public static function cached(): array
    {
        return Cache::rememberForever('app_settings', fn (): array => Setting::all()
            ->map(fn (Setting $setting): array => [
                'key' => $setting->key,
                'value' => $setting->value,
                'group' => $setting->group,
                'type' => $setting->type,
                'description' => $setting->description,
            ])
            ->all());
    }

    /**
     * @param  array{key: string, value: mixed}  $setting
     */
    private function updateMailers(array $setting, ?string $activeMailer): void
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
        } catch (\Exception $e) {
            Log::error('Error updating configs from settings: '.$e->getMessage());
        }
    }
}
