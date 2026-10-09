<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Settings\Models\Setting;
use Modules\Settings\Support\LegalHtml;

/**
 * The public privacy policy and terms pages (no login), for app stores, sign-up forms and
 * footers. Their text is what admins write under Settings → Privacy Policy / Terms & Conditions.
 * Turn them off with foundation.routing.legal_pages = false.
 */
final class LegalPageController extends Controller
{
    public function privacyPolicy(): View
    {
        return $this->page('privacy_policy');
    }

    public function termsConditions(): View
    {
        return $this->page('terms_conditions');
    }

    private function page(string $key): View
    {
        $setting = Setting::query()->where('key', $key)->first();
        $html = LegalHtml::clean(is_string($setting?->value) ? $setting->value : null);

        // Nothing to read (never written, or only markup left once cleaned): no page.
        abort_if(trim(html_entity_decode(strip_tags($html))) === '', 404);

        return view('settings::public.legal', [
            'title' => __("settings::settings.legal.{$key}"),
            'html' => $html,
            'updatedAt' => $setting?->updated_at,
        ]);
    }
}
