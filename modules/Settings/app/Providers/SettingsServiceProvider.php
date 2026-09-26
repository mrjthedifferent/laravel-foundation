<?php

namespace Modules\Settings\Providers;

use Illuminate\Support\Facades\Mail;
use Modules\Settings\Contracts\SecretCipher;
use Modules\Settings\Events\SettingsUpdated;
use Modules\Settings\Listeners\ReapplySettingsOnTenancyChange;
use Modules\Settings\Listeners\RestartQueueWorkers;
use Modules\Settings\Mail\Transport\MicrosoftGraphTransport;
use Modules\Settings\Mail\Transport\MicrosoftOAuthTransport;
use Modules\Settings\Models\Setting;
use Modules\Settings\Policies\SettingPolicy;
use Modules\Settings\Services\MailerSecretCipher;
use Modules\Settings\Services\MicrosoftOAuthTokenService;
use Modules\Settings\Support\EloquentSettingsRepository;
use Modules\Settings\Support\SettingsConfigApplier;
use Modules\Settings\View\Composers\SettingsWidgetComposer;
use Mrj\Foundation\Contracts\SettingsRepository;
use Mrj\Foundation\Events\TenancyContextChanged;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Mrj\Foundation\Support\Tenancy;
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
        $this->app->singleton(SettingsConfigApplier::class);
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
        // Each tenant keeps its own settings (foundation.tenancy): re-apply them when it changes.
        TenancyContextChanged::class => [ReapplySettingsOnTenancyChange::class],
    ];

    #[Override]
    public function boot(): void
    {
        parent::boot();

        $this->registerMicrosoftOAuthMailer();
        $this->registerMicrosoftGraphMailer();
        $this->app->make(SettingsConfigApplier::class)->apply();
    }

    /**
     * Register the "microsoft_oauth" mail transport, which authenticates to
     * Exchange Online with XOAUTH2 instead of a mailbox password.
     *
     * The credentials arrive in $config because SettingsConfigApplier copies
     * every key of the active mailer's VALUE into mail.mailers.{transport}.*.
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
     * The cache key every settings-cache read/write in the package goes
     * through, prefixed per foundation.cache.prefix and, with
     * foundation.tenancy enabled, scoped to the current tenant.
     */
    public static function cacheKey(): string
    {
        return Tenancy::cacheKey('app_settings');
    }
}
