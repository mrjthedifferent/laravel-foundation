<?php

namespace Modules\Settings\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Settings\Models\Setting;
use Mrj\Foundation\Http\Controllers\Controller;
use Mrj\Foundation\Http\Responses\JsonResponseFactory;

class ApiSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function appSettings(Request $request): JsonResponse
    {
        // $language = $request->header('Accept-Language');
        $settings = Setting::whereIn('group', ['Mobile App', 'Contact'])->get();
        $settings = $settings->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->value];
        })->toArray();

        // Resolve the Google Maps key for the requesting platform. The app sends
        // its platform via the X-Platform header (android|ios|web); we return the
        // matching key under the single `google_maps_api_key` field and drop the
        // per-platform keys so a client never receives another platform's key.
        $platform = strtolower((string) $request->header('X-Platform'));
        if (! in_array($platform, ['android', 'ios', 'web'], true)) {
            $platform = 'android';
        }

        $settings['google_maps_api_key'] = $settings["google_maps_api_key_{$platform}"] ?? '';
        unset(
            $settings['google_maps_api_key_android'],
            $settings['google_maps_api_key_ios'],
            $settings['google_maps_api_key_web'],
        );

        // Where an app links its "Privacy policy" and "Terms" (null when the public pages are off).
        $settings['privacy_policy_url'] = Route::has('legal.privacy_policy') ? route('legal.privacy_policy') : null;
        $settings['terms_conditions_url'] = Route::has('legal.terms_conditions') ? route('legal.terms_conditions') : null;
        $settings['account_deletion_url'] = Route::has('account.delete') ? route('account.delete') : null;

        return JsonResponseFactory::success(__('settings::settings.flash.api_settings_retrieved'), $settings);
    }

    /**
     * Get privacy policy content
     */
    public function privacyPolicy(): JsonResponse
    {
        $setting = Setting::where('key', 'privacy_policy')->first();

        if (! $setting) {
            return JsonResponseFactory::notFound(__('settings::settings.errors.api_privacy_policy_not_found'));
        }

        $data = [
            'privacy_policy' => $setting->value,
            'updated_at' => $setting->updated_at,
        ];

        return JsonResponseFactory::success(__('settings::settings.flash.api_privacy_policy_retrieved'), $data);
    }

    /**
     * Get terms and conditions content
     */
    public function termsConditions(): JsonResponse
    {
        $setting = Setting::where('key', 'terms_conditions')->first();

        if (! $setting) {
            return JsonResponseFactory::notFound(__('settings::settings.errors.api_terms_conditions_not_found'));
        }

        $data = [
            'terms_conditions' => $setting->value,
            'updated_at' => $setting->updated_at,
        ];

        return JsonResponseFactory::success(__('settings::settings.flash.api_terms_conditions_retrieved'), $data);
    }
}
