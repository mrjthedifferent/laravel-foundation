<?php

namespace Modules\Settings\Providers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Settings\Contracts\SecretCipher;
use Modules\Settings\Data\MailerData;
use Modules\Settings\Events\SettingsUpdated;
use Modules\Settings\Listeners\RestartQueueWorkers;
use Modules\Settings\Mail\Transport\MicrosoftGraphTransport;
use Modules\Settings\Mail\Transport\MicrosoftOAuthTransport;
use Modules\Settings\Models\Setting;
use Modules\Settings\Policies\SettingPolicy;
use Modules\Settings\Services\MailerSecretCipher;
use Modules\Settings\Services\MicrosoftOAuthTokenService;
use Modules\Settings\Support\EloquentSettingsRepository;
use Modules\Settings\View\Composers\SettingsWidgetComposer;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Override;

class SettingsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Settings';

    protected string $nameLower = 'settings';

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->bind(SecretCipher::class, MailerSecretCipher::class);
        $this->app->singleton(SettingsRepository::class, EloquentSettingsRepository::class);
    }

    protected array $morphMap = [
        'setting' => Setting::class,
    ];

    protected array $policies = [
        Setting::class => SettingPolicy::class,
    ];

    protected array $composers = [
        'settings::partials.dashboard-widget' => SettingsWidgetComposer::class,
    ];

    protected array $listen = [
        SettingsUpdated::class => [RestartQueueWorkers::class],
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

    #[Override]
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

    /**
     * Relies on the try/catch below rather than a Schema::hasTable() probe
     * (a query on every single request otherwise): a fresh install's very
     * first boot, before migrations run, simply finds no settings and hits
     * this catch, exactly as it would for any other database error.
     */
    private function updateConfigsFromSettings(): void
    {
        try {
            $settings = app(SettingsRepository::class)->all();

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
        } catch (Exception $e) {
            Log::error('Error updating configs from settings: '.$e->getMessage());
        }
    }

    /**
     * The cache key every settings-cache read/write in the package goes
     * through, prefixed per foundation.cache.prefix.
     */
    public static function cacheKey(): string
    {
        return config('foundation.cache.prefix').'app_settings';
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
        } catch (Exception $e) {
            Log::error('Error updating configs from settings: '.$e->getMessage());
        }
    }
}
