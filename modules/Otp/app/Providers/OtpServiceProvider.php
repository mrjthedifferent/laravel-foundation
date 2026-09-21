<?php

namespace Modules\Otp\Providers;

use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Policies\OtpWhitelistPolicy;
use Modules\Otp\View\Composers\OtpWidgetComposer;
use Mrj\Foundation\Support\ModuleServiceProvider;

class OtpServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Otp';

    protected string $nameLower = 'otp';

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
