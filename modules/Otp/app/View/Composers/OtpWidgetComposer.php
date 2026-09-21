<?php

namespace Modules\Otp\View\Composers;

use Illuminate\View\View;
use Modules\Otp\Models\OtpWhitelist;
use Modules\Otp\Models\VerificationCode;
use Mrj\Foundation\Services\Dashboard\DashboardCache;

/**
 * Supplies the OTP dashboard widget. The queries live here rather than in the
 * Blade partial so they can be cached and so the view stays free of data access.
 *
 * These are unscoped, application-wide totals, so a single shared cache key is safe.
 * A widget that ever becomes user-scoped must gain a scope segment in its key.
 */
final readonly class OtpWidgetComposer
{
    public function __construct(private DashboardCache $cache) {}

    public function compose(View $view): void
    {
        $view->with('widget', $this->data());
    }

    /**
     * @return array<string, mixed>|null null when the viewer may not see the widget
     */
    private function data(): ?array
    {
        if (! auth()->user()?->hasAnyPermission(['View OTP Whitelist', 'View Verification Code History'])) {
            return null;
        }

        return $this->cache->remember('widget:otp', fn (): array => [
            // `created_at` is a timestamp, so use a half-open range, not whereDate().
            'verified_today' => VerificationCode::query()
                ->where('created_at', '>=', today())
                ->where('created_at', '<', today()->addDay())
                ->where('is_verified', true)
                ->count(),
            'whitelist_count' => OtpWhitelist::query()->count(),
            'active_whitelist' => OtpWhitelist::query()->where('is_active', true)->count(),
        ]);
    }
}
