<?php

declare(strict_types=1);

namespace Modules\Otp\Providers;

use Modules\Otp\Actions\VerifyOtpAction;
use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Policies\OtpWhitelistPolicy;
use Modules\Otp\View\Composers\OtpWidgetComposer;
use Mrj\Foundation\Contracts\OtpVerifier;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Override;

class OtpServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Otp';

    protected string $nameLower = 'otp';

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->app->singleton(OtpVerifier::class, VerifyOtpAction::class);
    }

    protected array $morphMap = [
        'otp_whitelist' => OtpWhitelist::class,
    ];

    protected array $policies = [
        OtpWhitelist::class => OtpWhitelistPolicy::class,
    ];

    protected array $composers = [
        'otp::partials.dashboard-widget' => OtpWidgetComposer::class,
    ];
}
