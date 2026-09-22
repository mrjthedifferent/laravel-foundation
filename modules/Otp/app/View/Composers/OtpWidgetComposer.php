<?php

declare(strict_types=1);

namespace Modules\Otp\View\Composers;

use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Models\VerificationCode;
use Mrj\Foundation\Support\WidgetComposer;
use Override;

/**
 * Supplies the OTP dashboard widget.
 *
 * This is an unscoped, application-wide total, so a single shared cache key
 * is safe. A widget that ever becomes user-scoped must gain a scope segment
 * in its key.
 */
final class OtpWidgetComposer extends WidgetComposer
{
    #[Override]
    protected function permissions(): array
    {
        return ['View OTP Whitelist', 'View Verification Code History'];
    }

    #[Override]
    protected function key(): string
    {
        return 'otp';
    }

    #[Override]
    protected function build(): array
    {
        return [
            // `created_at` is a timestamp, so use a half-open range, not whereDate().
            'verified_today' => VerificationCode::query()
                ->where('created_at', '>=', today())
                ->where('created_at', '<', today()->addDay())
                ->where('is_verified', true)
                ->count(),
            'whitelist_count' => OtpWhitelist::query()->count(),
            'active_whitelist' => OtpWhitelist::query()->where('is_active', true)->count(),
        ];
    }
}
